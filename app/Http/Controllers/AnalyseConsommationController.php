<?php

namespace App\Http\Controllers;

use App\Services\Ia\AnalyseConsommationService;
use Illuminate\Http\Request;

/** Membre C — IA : profil de consommation (k-means), conseils personnalisés et économies estimées. */
class AnalyseConsommationController extends Controller
{
    public function __invoke(Request $request, AnalyseConsommationService $ia)
    {
        $user = $request->user();

        return view('ia.consommation', [
            'analyse' => $ia->conseils($user),
            'groupes' => $ia->profils()['groupes'],
            'compteurs' => $user->compteurs()->withCount('consommations')->get(),
        ]);
    }
}
