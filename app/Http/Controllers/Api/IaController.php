<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compteur;
use App\Models\Facture;
use App\Models\Installation;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\PrevisionProductionService;
use App\Services\Ia\RecommandationOffresService;
use App\Services\Ia\RisqueImpayeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Les fonctionnalités d'IA des trois membres, exposées en JSON (mêmes autorisations que le site). */
class IaController extends Controller
{
    public function risqueFacture(Facture $facture, RisqueImpayeService $risque): JsonResponse
    {
        $this->authorize('update', $facture); // réservé au vendeur et à l'admin

        return response()->json($risque->predire($facture) ?? ['message' => 'Modèle non entraîné.'], $risque->informations() ? 200 : 409);
    }

    public function recommandations(Request $request, RecommandationOffresService $service): JsonResponse
    {
        return response()->json($service->pour($request->user(), 5)->map(fn ($r) => [
            'offre' => $r['offre']->only('id', 'quantite_kwh', 'prix_kwh', 'date_fin') + ['installation' => $r['offre']->installation->nom],
            'score' => $r['score'],
            'raisons' => $r['raisons'],
        ]));
    }

    public function previsionInstallation(Request $request, Installation $installation, PrevisionProductionService $service): JsonResponse
    {
        $this->authorize('view', $installation);

        return response()->json([
            'prevision' => $service->prevoir($installation),
            'suggestion' => $request->user()->can('update', $installation) ? $service->suggererOffre($installation) : null,
        ]);
    }

    public function analyseCompteur(Compteur $compteur, AnalyseConsommationService $service): JsonResponse
    {
        $this->authorize('view', $compteur);

        return response()->json([
            'anomalies' => $service->anomalies($compteur),
            'prevision' => $service->prevoir($compteur),
        ]);
    }

    public function conseils(Request $request, AnalyseConsommationService $service): JsonResponse
    {
        return response()->json($service->conseils($request->user()));
    }
}
