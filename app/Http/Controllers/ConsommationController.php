<?php

namespace App\Http\Controllers;

use App\Models\Compteur;
use App\Models\Consommation;
use App\Services\ConsommationService;
use Illuminate\Http\Request;

class ConsommationController extends Controller
{
    public function __construct(private ConsommationService $service)
    {
    }

    /** Compteurs proposés dans les formulaires : ceux de l'utilisateur, actifs pour une saisie. */
    private function compteurs(Request $request, bool $actifsSeulement)
    {
        return Compteur::visiblePour($request->user())
            ->when($actifsSeulement, fn ($q) => $q->where('statut', 'actif'))
            ->orderBy('reference')->get();
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Consommation::class);

        $consommations = Consommation::visiblePour($request->user())->with('compteur')
            ->filtre($request->query())->latest('date')->paginate(15)->withQueryString();

        return view('consommations.index', [
            'consommations' => $consommations,
            'compteurs' => $this->compteurs($request, false),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Consommation::class);
        return view('consommations.create', ['compteurs' => $this->compteurs($request, true)]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Consommation::class);
        $this->service->create($request->validate($this->service->rules()), $request->user());
        return redirect()->route('consommations.index')->with('success', 'Relevé enregistré avec succès.');
    }

    public function show(Consommation $consommation)
    {
        $this->authorize('view', $consommation);
        $consommation->load('compteur.proprietaire');
        return view('consommations.show', compact('consommation'));
    }

    public function edit(Request $request, Consommation $consommation)
    {
        $this->authorize('update', $consommation);
        return view('consommations.edit', [
            'consommation' => $consommation,
            'compteurs' => $this->compteurs($request, false),
        ]);
    }

    public function update(Request $request, Consommation $consommation)
    {
        $this->authorize('update', $consommation);
        $this->service->update($consommation, $request->validate($this->service->rules($consommation)), $request->user());
        return redirect()->route('consommations.index')->with('success', 'Relevé modifié avec succès.');
    }

    public function destroy(Consommation $consommation)
    {
        $this->authorize('delete', $consommation);
        $consommation->delete();
        return redirect()->route('consommations.index')->with('success', 'Relevé supprimé.');
    }
}
