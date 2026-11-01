<?php

namespace App\Services\Ia;

use App\Ai\DetecteurAnomalies;
use App\Ai\KMeans;
use App\Ai\RegressionLineaire;
use App\Ai\Stats;
use App\Models\Compteur;
use App\Models\Consommation;
use App\Models\Echange;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Membre C — IA : analyse de la consommation.
 *
 * 1. Anomalies : score z robuste après correction du jour de la semaine (DetecteurAnomalies).
 * 2. Prévision : régression linéaire (tendance + effet de chaque jour de la semaine).
 * 3. Profils : k-means sur tous les consommateurs (moyenne, variabilité, part du week-end),
 *    puis des conseils personnalisés et une estimation des économies.
 */
class AnalyseConsommationService
{
    public const PROFILS = ['Sobre', 'Modéré', 'Intensif'];

    /** @return list<array{date: string, kwh: float}> */
    public function serie(Compteur $compteur): array
    {
        return $compteur->consommations()
            ->whereDate('date', '>=', today()->subDays(config('ia.consommation.historique_jours')))
            ->orderBy('date')->get(['date', 'energie_consommee_kwh'])
            ->map(fn ($c) => ['date' => $c->date->toDateString(), 'kwh' => (float) $c->energie_consommee_kwh])
            ->all();
    }

    /** @return list<array{date: string, kwh: float, score: float, sens: string}> */
    public function anomalies(Compteur $compteur): array
    {
        $serie = $this->serie($compteur);
        $jours = array_map(fn ($p) => CarbonImmutable::parse($p['date'])->dayOfWeekIso, $serie);

        $detectees = (new DetecteurAnomalies(config('ia.consommation.seuil_anomalie')))
            ->detecter(array_column($serie, 'kwh'), $jours);

        return array_map(fn ($a) => [
            'date' => $serie[$a['index']]['date'],
            'kwh' => $a['valeur'],
            'score' => round($a['score'], 2),
            'sens' => $a['sens'],
        ], $detectees);
    }

    /** @return array{prevision: list<array{date: string, kwh: float, bas: float, haut: float}>, total: float, r2: float}|null */
    public function prevoir(Compteur $compteur): ?array
    {
        $serie = $this->serie($compteur);
        if (count($serie) < 14) {
            return null;
        }

        $origine = CarbonImmutable::parse($serie[0]['date']);
        $X = array_map(fn ($p) => $this->variablesJour($origine, CarbonImmutable::parse($p['date'])), $serie);
        $y = array_column($serie, 'kwh');
        $modele = (new RegressionLineaire(0.1))->entrainer($X, $y);

        // Intervalle à 95 % à partir de l'écart-type des résidus (les anomalies sont exclues pour ne pas l'élargir à tort)
        $anomalies = array_column($this->anomalies($compteur), 'date');
        $residus = [];
        foreach ($X as $i => $x) {
            if (! in_array($serie[$i]['date'], $anomalies, true)) {
                $residus[] = $y[$i] - $modele->predire($x);
            }
        }
        $marge = 1.96 * Stats::ecartType($residus);

        $dernier = CarbonImmutable::parse(end($serie)['date']);
        $prevision = [];
        for ($h = 1; $h <= config('ia.consommation.horizon_jours'); $h++) {
            $jour = $dernier->addDays($h);
            $v = max(0, $modele->predire($this->variablesJour($origine, $jour)));
            $prevision[] = ['date' => $jour->toDateString(), 'kwh' => round($v, 2),
                'bas' => round(max(0, $v - $marge), 2), 'haut' => round($v + $marge, 2)];
        }

        return ['prevision' => $prevision, 'total' => round(array_sum(array_column($prevision, 'kwh')), 1), 'r2' => round($modele->r2($X, $y), 3)];
    }

    /**
     * Profils de consommation de tous les utilisateurs (k-means), mis en cache une heure.
     *
     * @return array{profils: array<int, string>, groupes: list<array{profil: string, membres: int, moyenne: float, variabilite: float, weekend: float}>}
     */
    public function profils(): array
    {
        return Cache::remember('ia.profils-consommation', 3600, fn () => $this->calculerProfils());
    }

    public function oublierProfils(): void
    {
        Cache::forget('ia.profils-consommation');
    }

    /** @return array{profil: ?string, conseils: list<string>, economies: ?array} */
    public function conseils(User $user): array
    {
        $profil = $this->profils()['profils'][$user->id] ?? null;
        $compteurs = $user->compteurs()->get();
        $conseils = [];

        $anomalies = $compteurs->flatMap(fn ($c) => $this->anomalies($c));
        $pics = $anomalies->where('sens', 'pic')->count();
        if ($pics > 0) {
            $conseils[] = "{$pics} pic(s) de consommation inhabituel(s) détecté(s) : vérifiez les appareils allumés ces jours-là (chauffage, climatisation, chauffe-eau).";
        }
        if ($anomalies->where('sens', 'chute')->count() > 0) {
            $conseils[] = 'Des chutes brutales de consommation peuvent indiquer un compteur défaillant : vérifiez son statut.';
        }

        $valeurs = $compteurs->flatMap(fn ($c) => $this->serie($c));
        if ($valeurs->count() >= 14) {
            $weekend = $valeurs->filter(fn ($p) => CarbonImmutable::parse($p['date'])->isWeekend())->avg('kwh');
            $semaine = $valeurs->reject(fn ($p) => CarbonImmutable::parse($p['date'])->isWeekend())->avg('kwh');
            if ($semaine > 0 && $weekend / $semaine > 1.3) {
                $conseils[] = 'Vous consommez nettement plus le week-end : décalez les gros usages (lave-linge, recharge) aux heures de production solaire.';
            }

            $moitie = intdiv($valeurs->count(), 2);
            $debut = $valeurs->take($moitie)->avg('kwh');
            $fin = $valeurs->skip($moitie)->avg('kwh');
            if ($debut > 0 && $fin / $debut > 1.15) {
                $conseils[] = sprintf('Votre consommation augmente (+%d %% sur la période) : surveillez cette tendance.', round(($fin / $debut - 1) * 100));
            } elseif ($debut > 0 && $fin / $debut < 0.9) {
                $conseils[] = 'Bonne nouvelle : votre consommation baisse sur la période.';
            }
        }

        $conseils[] = match ($profil) {
            'Intensif' => 'Profil intensif : acheter l\'énergie solaire de votre communauté est le levier d\'économie le plus fort pour vous.',
            'Modéré' => 'Profil modéré : quelques achats réguliers sur le marché local suffisent à réduire votre facture.',
            'Sobre' => 'Profil sobre : continuez ainsi ; si vous produisez, vous avez sans doute du surplus à vendre.',
            default => 'Ajoutez un compteur et au moins deux semaines de relevés pour obtenir votre profil.',
        };

        return ['profil' => $profil, 'conseils' => $conseils, 'economies' => $this->economies($compteurs)];
    }

    /** Économie mensuelle estimée si la consommation prévue est achetée sur le marché local plutôt qu'au réseau. */
    private function economies($compteurs): ?array
    {
        $total = $compteurs->map(fn ($c) => $this->prevoir($c)['total'] ?? 0)->sum();
        if ($total <= 0) {
            return null;
        }

        $prixMarche = Echange::whereIn('statut', ['accepte', 'termine'])->avg('prix_unitaire') ?: config('ia.prix_defaut_kwh');
        $reseau = config('ia.tarif_reseau_kwh');

        return [
            'kwh_prevus' => round($total, 1),
            'cout_reseau' => round($total * $reseau, 2),
            'cout_marche' => round($total * $prixMarche, 2),
            'economie' => round($total * max(0, $reseau - $prixMarche), 2),
        ];
    }

    /** Variables d'un jour : n° du jour (tendance) + 6 indicateurs du jour de la semaine (dimanche = référence). */
    private function variablesJour(CarbonImmutable $origine, CarbonImmutable $jour): array
    {
        $x = [(float) $origine->diffInDays($jour)];
        for ($j = 1; $j <= 6; $j++) {
            $x[] = $jour->dayOfWeekIso === $j ? 1.0 : 0.0;
        }

        return $x;
    }

    private function calculerProfils(): array
    {
        $parUtilisateur = Consommation::query()
            ->join('compteurs', 'compteurs.id', '=', 'consommations.compteur_id')
            ->whereDate('consommations.date', '>=', today()->subDays(config('ia.consommation.historique_jours')))
            ->get(['compteurs.user_id', 'consommations.date', 'consommations.energie_consommee_kwh'])
            ->groupBy('user_id')
            ->filter(fn ($r) => $r->count() >= 14);

        $k = config('ia.consommation.groupes');
        if ($parUtilisateur->count() < $k) {
            return ['profils' => [], 'groupes' => []];
        }

        $ids = [];
        $points = [];
        foreach ($parUtilisateur as $userId => $releves) {
            // un utilisateur peut avoir plusieurs compteurs : on additionne par jour
            $parJour = $releves->groupBy(fn ($r) => CarbonImmutable::parse($r->date)->toDateString())
                ->map(fn ($j) => $j->sum(fn ($r) => (float) $r->energie_consommee_kwh));
            $valeurs = $parJour->values()->all();
            $moyenne = Stats::moyenne($valeurs);
            $weekend = $parJour->filter(fn ($v, $d) => CarbonImmutable::parse($d)->isWeekend())->avg() ?? $moyenne;

            $ids[] = $userId;
            $points[] = [$moyenne, $moyenne > 0 ? Stats::ecartType($valeurs) / $moyenne : 0, $moyenne > 0 ? $weekend / $moyenne : 1];
        }

        [$Z] = Stats::standardiser($points);
        $km = (new KMeans($k))->entrainer($Z);
        $etiquettes = $km->etiquettes();

        // Les groupes sont nommés du moins au plus consommateur (moyenne brute)
        $moyennesGroupe = [];
        foreach (range(0, $k - 1) as $g) {
            $membres = array_keys(array_filter($etiquettes, fn ($e) => $e === $g));
            $moyennesGroupe[$g] = $membres ? Stats::moyenne(array_map(fn ($i) => $points[$i][0], $membres)) : INF;
        }
        asort($moyennesGroupe);
        $noms = [];
        foreach (array_keys($moyennesGroupe) as $rang => $g) {
            $noms[$g] = self::PROFILS[$rang] ?? 'Groupe '.($rang + 1);
        }

        $groupes = [];
        foreach (array_keys($moyennesGroupe) as $g) {
            $membres = array_keys(array_filter($etiquettes, fn ($e) => $e === $g));
            if ($membres === []) {
                continue;
            }
            $groupes[] = [
                'profil' => $noms[$g],
                'membres' => count($membres),
                'moyenne' => round(Stats::moyenne(array_map(fn ($i) => $points[$i][0], $membres)), 1),
                'variabilite' => round(Stats::moyenne(array_map(fn ($i) => $points[$i][1], $membres)), 2),
                'weekend' => round(Stats::moyenne(array_map(fn ($i) => $points[$i][2], $membres)), 2),
            ];
        }

        return [
            'profils' => array_combine($ids, array_map(fn ($e) => $noms[$e], $etiquettes)),
            'groupes' => $groupes,
        ];
    }
}
