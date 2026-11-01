<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Services\CsvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Membre B — fonctionnalité avancée : import / export CSV des productions et export des offres.
 * Import : colonnes "date" et "energie_produite_kwh" (ou "kwh"). Une ligne invalide est signalée
 * avec son numéro sans bloquer les autres ; une date déjà saisie est mise à jour.
 */
class ProductionCsvController extends Controller
{
    public function __construct(private CsvService $csv) {}

    public function import(Request $request, Installation $installation)
    {
        $this->authorize('update', $installation);
        $request->validate(['fichier' => 'required|file|mimes:csv,txt|max:2048']);

        $crees = $majs = 0;
        $erreurs = [];

        DB::transaction(function () use ($request, $installation, &$crees, &$majs, &$erreurs) {
            foreach ($this->csv->lire($request->file('fichier')) as $ligne) {
                $donnees = [
                    'date' => $ligne['date'] ?? '',
                    'energie_produite_kwh' => $this->csv->nombre($ligne['energie_produite_kwh'] ?? $ligne['kwh'] ?? ''),
                ];
                $v = Validator::make($donnees, [
                    'date' => 'required|date|before_or_equal:today',
                    'energie_produite_kwh' => 'required|numeric|min:0|max:100000',
                ]);
                if ($v->fails()) {
                    $erreurs[] = "Ligne {$ligne['_ligne']} : ".implode(' ', $v->errors()->all());

                    continue;
                }

                $existante = $installation->productions()->whereDate('date', $donnees['date'])->first();
                if ($existante) {
                    $existante->update($donnees);
                    $majs++;
                } else {
                    $installation->productions()->create($donnees);
                    $crees++;
                }
            }
        });

        $message = "Import terminé : {$crees} production(s) ajoutée(s), {$majs} mise(s) à jour.";

        return redirect()->route('installations.show', $installation)
            ->with('success', $message)
            ->with('erreurs_import', array_slice($erreurs, 0, 20));
    }

    public function exportProductions(Request $request)
    {
        $this->authorize('viewAny', Production::class);

        $productions = Production::with('installation')->filtre($request->query())->orderBy('date')->cursor();

        return $this->csv->exporter('productions-'.now()->format('Y-m-d').'.csv',
            ['installation', 'date', 'energie_produite_kwh'],
            (function () use ($productions) {
                foreach ($productions as $p) {
                    yield [$p->installation->nom, $p->date->toDateString(), $p->energie_produite_kwh];
                }
            })());
    }

    public function exportOffres(Request $request)
    {
        $this->authorize('viewAny', Offre::class);

        $offres = Offre::with('installation')->filtre($request->query())->orderBy('date_debut')->cursor();

        return $this->csv->exporter('offres-'.now()->format('Y-m-d').'.csv',
            ['installation', 'quantite_kwh', 'prix_kwh', 'date_debut', 'date_fin', 'statut'],
            (function () use ($offres) {
                foreach ($offres as $o) {
                    yield [$o->installation->nom, $o->quantite_kwh, $o->prix_kwh, $o->date_debut->toDateString(), $o->date_fin->toDateString(), $o->statut];
                }
            })());
    }
}
