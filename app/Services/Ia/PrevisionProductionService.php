<?php

namespace App\Services\Ia;

use App\Ai\LissageHolt;
use App\Ai\Stats;
use App\Models\Echange;
use App\Models\Installation;
use App\Models\Offre;
use Carbon\CarbonImmutable;

/**
 * Membre B — IA : prévision de la production d'une installation et suggestion d'offre.
 *
 * 1. Prévision : série quotidienne des derniers jours (trous comblés par interpolation),
 *    modèle de Holt (niveau + tendance) dont α et β sont appris sur l'historique.
 * 2. Suggestion : surplus prévu (production − autoconsommation, mesurée par le compteur lié
 *    s'il existe) borné par le stock, et prix ajusté selon l'offre et la demande du marché.
 */
class PrevisionProductionService
{
    /**
     * @return array{historique: list<array{date: string, kwh: float}>, prevision: list<array{date: string, kwh: float, bas: float, haut: float}>, metriques: array}|null
     */
    public function prevoir(Installation $installation): ?array
    {
        $serie = $this->serieQuotidienne($installation);
        if (count($serie) < config('ia.prevision.minimum_jours')) {
            return null;
        }

        $modele = (new LissageHolt())->entrainer(array_column($serie, 'kwh'));
        $dernier = CarbonImmutable::parse(end($serie)['date']);

        $prevision = [];
        foreach ($modele->prevoir(config('ia.prevision.horizon_jours')) as $h => $p) {
            $prevision[] = [
                'date' => $dernier->addDays($h + 1)->toDateString(),
                'kwh' => round($p['valeur'], 2),
                'bas' => round($p['bas'], 2),
                'haut' => round($p['haut'], 2),
            ];
        }

        return ['historique' => $serie, 'prevision' => $prevision, 'metriques' => $modele->metriques()];
    }

    /** @return array{quantite_kwh: float, prix_kwh: float, raisons: list<string>}|null */
    public function suggererOffre(Installation $installation): ?array
    {
        $prevision = $this->prevoir($installation);
        if (!$prevision) {
            return null;
        }

        $production = array_sum(array_column($prevision['prevision'], 'kwh'));
        [$autoconsommation, $source] = $this->autoconsommation($installation, $production);
        $surplus = max(0, $production - $autoconsommation);
        $stock = $installation->stockDisponible();
        $quantite = round(min($surplus, $stock), 2);

        [$prix, $raisonPrix] = $this->prixMarche();

        $raisons = [
            sprintf('Production prévue sur %d jours : %.1f kWh.', count($prevision['prevision']), $production),
            sprintf('Autoconsommation estimée : %.1f kWh (%s).', $autoconsommation, $source),
            sprintf('Stock encore proposable : %.1f kWh.', $stock),
            $raisonPrix,
        ];

        return ['quantite_kwh' => $quantite, 'prix_kwh' => $prix, 'raisons' => $raisons];
    }

    /** Série jour par jour ; les jours sans relevé sont estimés par interpolation linéaire. */
    private function serieQuotidienne(Installation $installation): array
    {
        $productions = $installation->productions()
            ->whereDate('date', '>=', today()->subDays(config('ia.prevision.historique_jours')))
            ->orderBy('date')->get(['date', 'energie_produite_kwh']);
        if ($productions->isEmpty()) {
            return [];
        }

        $valeurs = $productions->mapWithKeys(fn ($p) => [$p->date->toDateString() => (float) $p->energie_produite_kwh])->all();
        $debut = CarbonImmutable::parse($productions->first()->date);
        $fin = CarbonImmutable::parse($productions->last()->date);

        $serie = [];
        for ($d = $debut; $d->lte($fin); $d = $d->addDay()) {
            $serie[] = ['date' => $d->toDateString(), 'kwh' => $valeurs[$d->toDateString()] ?? null];
        }

        foreach ($serie as $i => $point) {
            if ($point['kwh'] === null) {
                $avant = $i - 1;
                $apres = $i + 1;
                while ($serie[$apres]['kwh'] === null) {
                    $apres++;
                }
                $t = ($i - $avant) / ($apres - $avant);
                $serie[$i]['kwh'] = round($serie[$avant]['kwh'] + $t * ($serie[$apres]['kwh'] - $serie[$avant]['kwh']), 2);
            }
        }
        return $serie;
    }

    /** Consommation moyenne mesurée par le compteur lié (lien avec le module du membre C), sinon un ratio par défaut. */
    private function autoconsommation(Installation $installation, float $production): array
    {
        $compteur = $installation->compteurs()->where('statut', 'actif')->first();
        $releves = $compteur?->consommations()->whereDate('date', '>=', today()->subDays(30))->pluck('energie_consommee_kwh');

        if ($releves && $releves->count() >= 7) {
            $parJour = Stats::moyenne($releves->map(fn ($v) => (float) $v)->all());
            return [$parJour * config('ia.prevision.horizon_jours'), "mesurée par le compteur {$compteur->reference}"];
        }

        $ratio = config('ia.prevision.autoconsommation_defaut');
        return [$production * $ratio, 'estimation par défaut ' . ($ratio * 100) . ' %'];
    }

    /** Prix de référence (médiane des échanges conclus) ajusté par le rapport offre / demande. */
    private function prixMarche(): array
    {
        $prixConclus = Echange::whereIn('statut', ['accepte', 'termine'])
            ->where('date_echange', '>=', now()->subDays(60))->pluck('prix_unitaire')->map(fn ($v) => (float) $v)->all();
        $reference = $prixConclus !== [] ? Stats::mediane($prixConclus) : (float) config('ia.prix_defaut_kwh');

        $offre = (float) Offre::where('statut', 'disponible')->sum('quantite_kwh');
        $demande = (float) Echange::where('date_echange', '>=', now()->subDays(30))->sum('quantite_kwh');
        $tension = $offre + $demande > 0 ? ($demande - $offre) / ($demande + $offre) : 0.0; // entre -1 et 1

        $prix = round(max(0.5 * $reference, min(1.5 * $reference, $reference * (1 + 0.2 * $tension))), 4);
        $sens = $tension > 0.05 ? 'demande supérieure à l\'offre : prix relevé' : ($tension < -0.05 ? 'offre abondante : prix baissé' : 'marché équilibré');

        return [$prix, sprintf('Prix médian des échanges récents : %.4f, %s.', $reference, $sens)];
    }
}
