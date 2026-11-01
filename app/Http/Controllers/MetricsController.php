<?php

namespace App\Http\Controllers;

use App\Models\Compteur;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * DevOps — métriques au format texte Prometheus, lues par Prometheus puis affichées dans Grafana.
 * Protégé par le jeton METRICS_TOKEN (en-tête "Authorization: Bearer ..."). Sans jeton configuré,
 * la route n'est ouverte qu'en environnement local.
 */
class MetricsController extends Controller
{
    public function __invoke(Request $request)
    {
        $jeton = config('services.metrics.token');
        abort_if($jeton ? !hash_equals($jeton, (string) $request->bearerToken()) : !app()->isLocal(), 403);

        $debut = microtime(true);
        $baseOk = rescue(fn () => DB::select('select 1') !== null, false, false);
        $latenceBase = microtime(true) - $debut;

        $lignes = [];
        $metrique = function (string $nom, string $aide, string $type, array $valeurs) use (&$lignes) {
            $lignes[] = "# HELP solarshare_{$nom} {$aide}";
            $lignes[] = "# TYPE solarshare_{$nom} {$type}";
            foreach ($valeurs as [$labels, $valeur]) {
                $l = $labels ? '{' . implode(',', array_map(fn ($k, $v) => "{$k}=\"{$v}\"", array_keys($labels), $labels)) . '}' : '';
                $lignes[] = "solarshare_{$nom}{$l} " . (is_float($valeur) ? round($valeur, 4) : $valeur);
            }
        };

        $metrique('up', 'Application et base de données joignables (1 = oui)', 'gauge', [[[], $baseOk ? 1 : 0]]);
        $metrique('database_latency_seconds', 'Durée d\'une requête simple à la base', 'gauge', [[[], $latenceBase]]);

        if ($baseOk) {
            $metrique('users_total', 'Nombre d\'utilisateurs', 'gauge', [[[], User::count()]]);
            $metrique('installations_total', 'Nombre d\'installations', 'gauge', [[[], Installation::count()]]);
            $metrique('offres', 'Offres par statut', 'gauge',
                array_map(fn ($s) => [['statut' => $s], Offre::where('statut', $s)->count()], Offre::STATUTS));
            $metrique('offres_disponibles_kwh', 'Énergie proposée à la vente', 'gauge',
                [[[], (float) Offre::where('statut', 'disponible')->sum('quantite_kwh')]]);
            $metrique('echanges', 'Échanges par statut', 'gauge',
                array_map(fn ($s) => [['statut' => $s], Echange::where('statut', $s)->count()], Echange::STATUTS));
            $metrique('factures_montant', 'Montant des factures par statut (DT)', 'gauge',
                array_map(fn ($s) => [['statut' => $s], (float) Facture::where('statut', $s)->sum('montant_total')], Facture::STATUTS));
            $metrique('factures_en_retard', 'Factures impayées après l\'échéance', 'gauge',
                [[[], Facture::where('statut', 'impayee')->whereDate('date_echeance', '<', today())->count()]]);
            $metrique('compteurs_en_panne', 'Compteurs signalés en panne', 'gauge', [[[], Compteur::where('statut', 'en_panne')->count()]]);
            $metrique('jobs_en_attente', 'Tâches en file d\'attente', 'gauge', [[[], DB::table('jobs')->count()]]);
            $metrique('jobs_echoues', 'Tâches en échec', 'gauge', [[[], DB::table('failed_jobs')->count()]]);
        }

        $modele = Storage::disk('local')->exists(config('ia.risque.fichier'))
            ? json_decode(Storage::disk('local')->get(config('ia.risque.fichier')), true) : null;
        $metrique('ia_modele_risque_auc', 'AUC du modèle de risque d\'impayé (0 si non entraîné)', 'gauge',
            [[[], (float) ($modele['metriques']['auc'] ?? 0)]]);

        return response(implode("\n", $lignes) . "\n", 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}
