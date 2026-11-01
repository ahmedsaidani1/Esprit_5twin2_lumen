<?php

namespace App\Services\Ia;

use App\Ai\Stats;
use App\Models\Echange;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Membre A — IA : système de recommandation d'offres pour un acheteur.
 *
 * Filtrage par le contenu : le profil de l'acheteur (prix et quantités habituels, communauté) est
 * comparé à chaque offre disponible ; la fiabilité du vendeur (taux d'acceptation) est ajoutée.
 * Sans historique (démarrage à froid), on privilégie le prix et la proximité.
 */
class RecommandationOffresService
{
    /** @return Collection<int, array{offre: Offre, score: float, raisons: list<string>}> */
    public function pour(User $acheteur, int $nombre = 3): Collection
    {
        Offre::expireOutdated();

        $offres = Offre::with('installation.proprietaire')
            ->where('statut', 'disponible')
            ->whereHas('installation', fn ($q) => $q->where('user_id', '!=', $acheteur->id))
            ->get();
        if ($offres->isEmpty()) {
            return collect();
        }

        $achats = Echange::where('acheteur_id', $acheteur->id)->whereIn('statut', ['accepte', 'termine'])->get();
        $prixHabituel = $achats->isNotEmpty() ? Stats::mediane($achats->pluck('prix_unitaire')->map(fn ($v) => (float) $v)->all()) : null;
        $quantiteHabituelle = $achats->isNotEmpty() ? Stats::mediane($achats->pluck('quantite_kwh')->map(fn ($v) => (float) $v)->all()) : null;

        $prixMin = (float) $offres->min('prix_kwh');
        $prixMax = (float) $offres->max('prix_kwh');
        $fiabilites = $this->fiabiliteVendeurs($offres->pluck('installation.user_id')->unique()->all());

        // Poids : avec historique, on personnalise ; sans historique, prix et proximité comptent plus.
        $poids = $achats->isNotEmpty()
            ? ['prix' => 0.30, 'habitude_prix' => 0.15, 'quantite' => 0.20, 'communaute' => 0.20, 'fiabilite' => 0.15]
            : ['prix' => 0.45, 'habitude_prix' => 0.0, 'quantite' => 0.0, 'communaute' => 0.35, 'fiabilite' => 0.20];

        return $offres->map(function (Offre $o) use ($acheteur, $prixMin, $prixMax, $prixHabituel, $quantiteHabituelle, $fiabilites, $poids) {
            $prix = (float) $o->prix_kwh;
            $s = [
                'prix' => $prixMax > $prixMin ? 1 - ($prix - $prixMin) / ($prixMax - $prixMin) : 1.0,
                'habitude_prix' => $prixHabituel ? exp(-abs($prix - $prixHabituel) / max(0.01, $prixHabituel)) : 0.0,
                'quantite' => $quantiteHabituelle ? exp(-abs(log(max(0.01, (float) $o->quantite_kwh) / $quantiteHabituelle))) : 0.0,
                'communaute' => $acheteur->communaute_id && $o->installation->communaute_id === $acheteur->communaute_id ? 1.0 : 0.0,
                'fiabilite' => $fiabilites[$o->installation->user_id] ?? 0.5,
            ];
            $score = array_sum(array_map(fn ($k) => $poids[$k] * $s[$k], array_keys($s)));

            $raisons = [];
            if ($s['prix'] >= 0.7) {
                $raisons[] = 'prix parmi les plus bas';
            }
            if ($s['communaute'] === 1.0) {
                $raisons[] = 'dans votre communauté';
            }
            if ($s['quantite'] >= 0.7) {
                $raisons[] = 'quantité proche de vos achats habituels';
            }
            if ($s['fiabilite'] >= 0.75) {
                $raisons[] = 'vendeur fiable';
            }

            return ['offre' => $o, 'score' => round($score, 3), 'raisons' => $raisons ?: ['offre disponible']];
        })->sortByDesc('score')->take($nombre)->values();
    }

    /** Taux d'acceptation des vendeurs, lissé (Laplace) pour ne pas pénaliser les nouveaux. */
    private function fiabiliteVendeurs(array $vendeurs): array
    {
        return Echange::whereIn('vendeur_id', $vendeurs)
            ->whereIn('statut', ['accepte', 'termine', 'refuse'])
            ->get(['vendeur_id', 'statut'])
            ->groupBy('vendeur_id')
            ->map(fn ($e) => ($e->whereIn('statut', ['accepte', 'termine'])->count() + 1) / ($e->count() + 2))
            ->all();
    }
}
