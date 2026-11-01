<?php

namespace App\Http\Controllers;

use App\Models\Compteur;
use App\Models\Consommation;
use App\Services\ConsommationService;
use App\Services\CsvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Membre C — fonctionnalité avancée : import / export CSV des relevés.
 * Import : colonnes "date" et "energie_consommee_kwh" (ou "kwh"), mêmes règles que la saisie
 * (compteur actif, un relevé par jour). Les lignes refusées sont listées avec leur numéro.
 */
class ConsommationCsvController extends Controller
{
    public function __construct(private CsvService $csv, private ConsommationService $service)
    {
    }

    public function import(Request $request, Compteur $compteur)
    {
        $this->authorize('update', $compteur);
        $request->validate(['fichier' => 'required|file|mimes:csv,txt|max:2048']);

        $importes = 0;
        $erreurs = [];

        foreach ($this->csv->lire($request->file('fichier')) as $ligne) {
            $donnees = [
                'compteur_id' => $compteur->id,
                'date' => $ligne['date'] ?? '',
                'energie_consommee_kwh' => $this->csv->nombre($ligne['energie_consommee_kwh'] ?? $ligne['kwh'] ?? ''),
            ];

            $v = Validator::make($donnees, $this->service->rules());
            if ($v->fails()) {
                $erreurs[] = "Ligne {$ligne['_ligne']} : " . implode(' ', $v->errors()->all());
                continue;
            }
            try {
                $this->service->create($v->validated(), $request->user());
                $importes++;
            } catch (ValidationException $e) {
                $erreurs[] = "Ligne {$ligne['_ligne']} : " . implode(' ', $e->validator->errors()->all());
            }
        }

        return redirect()->route('compteurs.show', $compteur)
            ->with('success', "Import terminé : {$importes} relevé(s) ajouté(s)" . ($erreurs ? ', ' . count($erreurs) . ' ligne(s) refusée(s).' : '.'))
            ->with('erreurs_import', array_slice($erreurs, 0, 20));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Consommation::class);

        $releves = Consommation::visiblePour($request->user())->with('compteur')
            ->filtre($request->query())->orderBy('date')->cursor();

        return $this->csv->exporter('consommations-' . now()->format('Y-m-d') . '.csv',
            ['compteur', 'date', 'energie_consommee_kwh'],
            (function () use ($releves) {
                foreach ($releves as $r) {
                    yield [$r->compteur->reference, $r->date->toDateString(), $r->energie_consommee_kwh];
                }
            })());
    }
}
