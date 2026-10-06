<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Production;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    private function rules(): array
    {
        return [
            'installation_id'      => 'required|exists:installations,id',
            'date'                 => 'required|date',
            'energie_produite_kwh' => 'required|numeric|min:0',
        ];
    }

    public function index(Request $request)
    {
        $productions = Production::with('installation')->filtre($request->query())
            ->latest('date')->paginate(10)->withQueryString();
        return view('productions.index', [
            'productions' => $productions,
            'installations' => Installation::orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('productions.create', ['installations' => Installation::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        Production::create($request->validate($this->rules()));
        return redirect()->route('productions.index')->with('success', 'Production enregistrée avec succès.');
    }

    public function show(Production $production)
    {
        $production->load('installation.proprietaire');
        return view('productions.show', compact('production'));
    }

    public function edit(Production $production)
    {
        return view('productions.edit', [
            'production' => $production,
            'installations' => Installation::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Production $production)
    {
        $production->update($request->validate($this->rules()));
        return redirect()->route('productions.index')->with('success', 'Production modifiée avec succès.');
    }

    public function destroy(Production $production)
    {
        $production->delete();
        return redirect()->route('productions.index')->with('success', 'Production supprimée.');
    }
}
