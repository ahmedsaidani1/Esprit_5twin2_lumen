<?php

namespace App\Services;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;

/**
 * Chiffres du tableau de bord. L'énergie et les offres sont publiques (marché),
 * mais les échanges et les factures sont limités à ceux de l'utilisateur (sauf admin).
 */
class StatsService
{
    public function summary(User $user): array
    {
        Offre::expireOutdated();

        $echanges = fn () => Echange::visiblePour($user);
        $factures = fn () => Facture::visiblePour($user);

        return [
            'compteurs' => [
                'utilisateurs'  => User::count(),
                'communautes'   => Communaute::count(),
                'installations' => Installation::count(),
                'productions'   => Production::count(),
                'offres'        => Offre::count(),
                'echanges'      => $echanges()->count(),
                'factures'      => $factures()->count(),
            ],
            'energie' => [
                'produite_kwh'            => (float) Production::sum('energie_produite_kwh'),
                'produite_30_jours_kwh'   => (float) Production::whereDate('date', '>=', today()->subDays(30))->sum('energie_produite_kwh'),
                'offerte_disponible_kwh'  => (float) Offre::where('statut', 'disponible')->sum('quantite_kwh'),
                'echangee_kwh'            => (float) $echanges()->whereIn('statut', ['accepte', 'termine'])->sum('quantite_kwh'),
            ],
            'offres_par_statut' => $this->countBy(Offre::query(), Offre::STATUTS),
            'echanges_par_statut' => $this->countBy($echanges(), Echange::STATUTS),
            'factures' => [
                'payee'   => (float) $factures()->where('statut', 'payee')->sum('montant_total'),
                'impayee' => (float) $factures()->where('statut', 'impayee')->sum('montant_total'),
                'annulee' => (float) $factures()->where('statut', 'annulee')->sum('montant_total'),
            ],
            'production_par_installation' => Installation::withSum('productions as total_kwh', 'energie_produite_kwh')
                ->orderByDesc('total_kwh')->limit(5)->get(['id', 'nom'])
                ->map(fn ($i) => ['id' => $i->id, 'nom' => $i->nom, 'total_kwh' => (float) $i->total_kwh])->all(),
        ];
    }

    private function countBy($query, array $statuts): array
    {
        $counts = $query->selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        return collect($statuts)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all();
    }
}
