<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Offre;
use App\Services\StatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, StatsService $stats)
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => $stats->summary($user),
            'derniersEchanges' => Echange::visiblePour($user)->with('vendeur', 'acheteur')
                ->latest('date_echange')->limit(5)->get(),
            'offresOuvertes' => Offre::with('installation')->where('statut', 'disponible')->latest()->limit(5)->get(),
        ]);
    }
}
