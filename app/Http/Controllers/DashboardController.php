<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Offre;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\RecommandationOffresService;
use App\Services\StatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, StatsService $stats, RecommandationOffresService $recommandations, AnalyseConsommationService $consommation)
    {
        $user = $request->user();

        // Membre C — IA : anomalies récentes (14 derniers jours) sur les compteurs de l'utilisateur
        $alertes = $user->compteurs()->where('statut', 'actif')->get()
            ->flatMap(fn ($c) => collect($consommation->anomalies($c))
                ->filter(fn ($a) => $a['date'] >= today()->subDays(14)->toDateString())
                ->map(fn ($a) => $a + ['compteur' => $c]));

        return view('dashboard', [
            'stats' => $stats->summary($user),
            'derniersEchanges' => Echange::visiblePour($user)->with('vendeur', 'acheteur')
                ->latest('date_echange')->limit(5)->get(),
            'offresOuvertes' => Offre::with('installation')->where('statut', 'disponible')->latest()->limit(5)->get(),
            'recommandations' => $recommandations->pour($user),
            'alertes' => $alertes,
        ]);
    }
}
