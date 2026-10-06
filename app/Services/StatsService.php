<?php

namespace App\Services;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;

class StatsService
{
    public function summary(): array
    {
        Offre::expireOutdated();

        return [
            'compteurs' => [
                'utilisateurs'  => User::count(),
                'communautes'   => Communaute::count(),
                'installations' => Installation::count(),
                'productions'   => Production::count(),
                'offres'        => Offre::count(),
                'echanges'      => Echange::count(),
                'factures'      => Facture::count(),
            ],
            'energie' => [
                'produite_kwh'            => (float) Production::sum('energie_produite_kwh'),
                'produite_30_jours_kwh'   => (float) Production::whereDate('date', '>=', today()->subDays(30))->sum('energie_produite_kwh'),
                'offerte_disponible_kwh'  => (float) Offre::where('statut', 'disponible')->sum('quantite_kwh'),
                'echangee_kwh'            => (float) Echange::whereIn('statut', ['accepte', 'termine'])->sum('quantite_kwh'),
            ],
            'offres_par_statut' => $this->countBy(Offre::class, Offre::STATUTS),
            'echanges_par_statut' => $this->countBy(Echange::class, Echange::STATUTS),
            'factures' => [
                'payee'   => (float) Facture::where('statut', 'payee')->sum('montant_total'),
                'impayee' => (float) Facture::where('statut', 'impayee')->sum('montant_total'),
                'annulee' => (float) Facture::where('statut', 'annulee')->sum('montant_total'),
            ],
            'production_par_installation' => Installation::withSum('productions as total_kwh', 'energie_produite_kwh')
                ->orderByDesc('total_kwh')->limit(5)->get(['id', 'nom'])
                ->map(fn ($i) => ['id' => $i->id, 'nom' => $i->nom, 'total_kwh' => (float) $i->total_kwh])->all(),
        ];
    }

    private function countBy(string $model, array $statuts): array
    {
        $counts = $model::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        return collect($statuts)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->all();
    }
}
