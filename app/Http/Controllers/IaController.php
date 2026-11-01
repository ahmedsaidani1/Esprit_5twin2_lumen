<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\PrevisionProductionService;
use App\Services\Ia\RecommandationOffresService;
use App\Services\Ia\RisqueImpayeService;
use Illuminate\Http\Request;

/**
 * "Laboratoire IA" : une page qui montre et explique les modèles des trois membres,
 * avec leurs mesures de qualité. L'administrateur peut relancer l'entraînement.
 */
class IaController extends Controller
{
    public function index(
        Request $request,
        RisqueImpayeService $risque,
        RecommandationOffresService $recommandations,
        PrevisionProductionService $prevision,
        AnalyseConsommationService $consommation,
    ) {
        // Qualité des prévisions de production sur les installations de l'utilisateur (toutes pour l'admin)
        $previsions = Installation::gereesPar($request->user())->orderBy('nom')->get()
            ->map(fn ($i) => ['installation' => $i, 'resultat' => $prevision->prevoir($i)])
            ->filter(fn ($p) => $p['resultat'] !== null)->values();

        return view('ia.index', [
            'modeleRisque' => $risque->informations(),
            'variablesRisque' => RisqueImpayeService::VARIABLES,
            'recommandations' => $recommandations->pour($request->user()),
            'previsions' => $previsions,
            'groupes' => $consommation->profils()['groupes'],
        ]);
    }

    public function entrainer(RisqueImpayeService $risque, AnalyseConsommationService $consommation)
    {
        $resultat = $risque->entrainer();
        $consommation->oublierProfils();

        return back()->with($resultat['entraine'] ? 'success' : 'status', $resultat['entraine']
            ? sprintf('Modèle de risque réentraîné sur %d factures (AUC %.2f). Profils de consommation recalculés.',
                $resultat['exemples']['total'], $resultat['metriques']['auc'])
            : $resultat['raison']);
    }
}
