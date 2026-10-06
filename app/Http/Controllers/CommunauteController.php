<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use Illuminate\Http\Request;

class CommunauteController extends Controller
{
    private function rules(): array
    {
        return [
            'nom'   => 'required|string|max:255',
            'ville' => 'nullable|string|max:255',
        ];
    }

    public function index(Request $request)
    {
        $communautes = Communaute::withCount(['installations', 'echanges'])
            ->filtre($request->query())->orderBy('nom')->paginate(10)->withQueryString();
        return view('communautes.index', compact('communautes'));
    }

    public function create()
    {
        return view('communautes.create');
    }

    public function store(Request $request)
    {
        Communaute::create($request->validate($this->rules()));
        return redirect()->route('communautes.index')->with('success', 'Communauté créée avec succès.');
    }

    public function show(Communaute $communaute)
    {
        $communaute->load('installations.proprietaire')->loadCount('echanges');
        return view('communautes.show', compact('communaute'));
    }

    public function edit(Communaute $communaute)
    {
        return view('communautes.edit', compact('communaute'));
    }

    public function update(Request $request, Communaute $communaute)
    {
        $communaute->update($request->validate($this->rules()));
        return redirect()->route('communautes.index')->with('success', 'Communauté modifiée avec succès.');
    }

    public function destroy(Communaute $communaute)
    {
        if ($communaute->echanges()->exists()) {
            return back()->withErrors(['communaute' => 'Impossible de supprimer : cette communauté a des échanges.']);
        }

        $communaute->delete();
        return redirect()->route('communautes.index')->with('success', 'Communauté supprimée.');
    }
}
