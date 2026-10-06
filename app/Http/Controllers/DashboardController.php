<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Offre;
use App\Services\StatsService;

class DashboardController extends Controller
{
    public function index(StatsService $stats)
    {
        return view('dashboard', [
            'stats' => $stats->summary(),
            'derniersEchanges' => Echange::with('vendeur', 'acheteur')->latest('date_echange')->limit(5)->get(),
            'offresOuvertes' => Offre::with('installation')->where('statut', 'disponible')->latest()->limit(5)->get(),
        ]);
    }
}
