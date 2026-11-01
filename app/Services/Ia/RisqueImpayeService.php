<?php

namespace App\Services\Ia;

use App\Ai\RegressionLogistique;
use App\Models\Echange;
use App\Models\Facture;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Membre A — IA : probabilité qu'une facture ne soit pas payée à temps.
 *
 * Modèle : régression logistique entraînée sur l'historique des factures dont l'issue est connue
 * (payée = 0 ; impayée après l'échéance = 1). Le modèle est sauvegardé en JSON et rechargé
 * pour les prédictions. Chaque prédiction est expliquée par la contribution de chaque variable.
 */
class RisqueImpayeService
{
    public const VARIABLES = [
        'montant' => 'Montant de la facture',
        'taux_impayes' => 'Impayés passés de l\'acheteur',
        'historique' => 'Nombre d\'échanges passés de l\'acheteur',
        'delai' => 'Délai de paiement accordé',
        'hors_communaute' => 'Acheteur hors de la communauté de l\'échange',
    ];

    private ?array $modele = null;

    /** Entraîne le modèle, l'évalue sur 25 % des données mises de côté, et le sauvegarde. */
    public function entrainer(): array
    {
        $exemples = $this->exemplesEtiquetes();
        $nbPositifs = $exemples->where('y', 1)->count();
        $minimum = config('ia.risque.minimum_exemples');

        if ($exemples->count() < $minimum || $nbPositifs === 0 || $nbPositifs === $exemples->count()) {
            return ['entraine' => false, 'raison' => "Pas assez d'historique : {$exemples->count()} factures dont {$nbPositifs} impayées (minimum {$minimum}, avec les deux issues)."];
        }

        // Séparation déterministe entraînement / test (1 facture sur 4 en test)
        [$test, $train] = $exemples->partition(fn ($e) => $e['id'] % 4 === 0);
        if ($test->pluck('y')->unique()->count() < 2) {
            $test = $exemples; // trop peu de données : on évalue sur tout l'historique
        }

        $modele = (new RegressionLogistique(0.3, 1500, 0.01, equilibrerClasses: true))
            ->entrainer($train->pluck('x')->values()->all(), $train->pluck('y')->values()->all());
        $metriques = $modele->evaluer($test->pluck('x')->values()->all(), $test->pluck('y')->values()->all());

        $this->modele = [
            'modele' => $modele->versTableau(),
            'variables' => array_keys(self::VARIABLES),
            'metriques' => $metriques,
            'exemples' => ['total' => $exemples->count(), 'impayes' => $nbPositifs, 'entrainement' => $train->count(), 'test' => $test->count()],
            'entraine_le' => now()->toIso8601String(),
        ];
        Storage::disk('local')->put(config('ia.risque.fichier'), json_encode($this->modele, JSON_PRETTY_PRINT));

        return ['entraine' => true] + $this->modele;
    }

    /** @return array{probabilite: float, niveau: string, facteurs: list<array{libelle: string, effet: float}>}|null */
    public function predire(Facture $facture): ?array
    {
        $donnees = $this->charger();
        if (! $donnees) {
            return null;
        }

        $modele = RegressionLogistique::depuisTableau($donnees['modele']);
        $x = $this->variables($facture);
        $p = $modele->probabilite($x);

        $facteurs = collect($modele->contributions($x))
            ->map(fn ($effet, $j) => ['libelle' => array_values(self::VARIABLES)[$j], 'effet' => $effet])
            ->sortByDesc(fn ($f) => abs($f['effet']))->take(3)->values()->all();

        return ['probabilite' => $p, 'niveau' => $this->niveau($p), 'facteurs' => $facteurs];
    }

    public function informations(): ?array
    {
        return $this->charger();
    }

    public function niveau(float $p): string
    {
        $seuils = config('ia.risque.seuils');

        return $p >= $seuils['eleve'] ? 'élevé' : ($p >= $seuils['moyen'] ? 'moyen' : 'faible');
    }

    /**
     * Variables d'une facture, calculées uniquement avec ce qui était connu à sa date d'émission
     * (pas de "fuite" d'information du futur dans l'entraînement).
     *
     * @return list<float>
     */
    public function variables(Facture $facture): array
    {
        $echange = $facture->echange;
        $acheteur = $echange->acheteur;

        $passees = Facture::query()
            ->whereHas('echange', fn ($q) => $q->where('acheteur_id', $acheteur->id))
            ->where('date_emission', '<', $facture->date_emission)
            ->whereKeyNot($facture->id)
            ->get(['statut', 'date_echeance']);
        $impayees = $passees->filter(fn ($f) => $f->statut === 'impayee' && $f->date_echeance->lt($facture->date_emission))->count();

        $nbEchanges = Echange::where('acheteur_id', $acheteur->id)
            ->where('date_echange', '<', $echange->date_echange)->count();

        return [
            log(1 + (float) $facture->montant_total),
            ($impayees + 1) / ($passees->count() + 2), // lissage de Laplace : 50 % sans historique
            log(1 + $nbEchanges),
            (float) $facture->date_emission->diffInDays($facture->date_echeance),
            (float) ($acheteur->communaute_id !== $echange->communaute_id),
        ];
    }

    /** Factures dont on connaît l'issue : payées (0) ou impayées après échéance (1). */
    private function exemplesEtiquetes(): Collection
    {
        return Facture::with('echange.acheteur')
            ->where(fn ($q) => $q->where('statut', 'payee')
                ->orWhere(fn ($q) => $q->where('statut', 'impayee')->whereDate('date_echeance', '<', today())))
            ->get()
            ->map(fn (Facture $f) => ['id' => $f->id, 'x' => $this->variables($f), 'y' => $f->statut === 'impayee' ? 1 : 0]);
    }

    private function charger(): ?array
    {
        if ($this->modele === null && Storage::disk('local')->exists(config('ia.risque.fichier'))) {
            $this->modele = json_decode(Storage::disk('local')->get(config('ia.risque.fichier')), true);
        }

        return $this->modele;
    }
}
