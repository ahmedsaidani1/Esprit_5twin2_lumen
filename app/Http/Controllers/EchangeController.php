<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\User;
use Illuminate\Http\Request;

class EchangeController extends Controller
{
    private function rules(): array
    {
        return [
            'vendeur_id'    => 'required|exists:users,id',
            'acheteur_id'   => 'required|exists:users,id|different:vendeur_id',
            'communaute_id' => 'required|exists:communautes,id',
            'quantite_kwh'  => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0',
            'date_echange'  => 'required|date',
            'statut'        => 'required|in:' . implode(',', Echange::STATUTS),
        ];
    }

    public function index()
    {
        $echanges = Echange::with(['vendeur', 'acheteur', 'communaute', 'facture'])
            ->latest('date_echange')->paginate(10);
        return view('echanges.index', compact('echanges'));
    }

    public function create()
    {
        return view('echanges.create', [
            'users' => User::orderBy('name')->get(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Echange::create($request->validate($this->rules()));
        return redirect()->route('echanges.index')->with('success', 'Échange créé avec succès.');
    }

    public function show(Echange $echange)
    {
        $echange->load(['vendeur', 'acheteur', 'communaute', 'facture']);
        return view('echanges.show', compact('echange'));
    }

    public function edit(Echange $echange)
    {
        return view('echanges.edit', [
            'echange' => $echange,
            'users' => User::orderBy('name')->get(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Echange $echange)
    {
        $echange->update($request->validate($this->rules()));
        return redirect()->route('echanges.index')->with('success', 'Échange modifié avec succès.');
    }

    public function destroy(Echange $echange)
    {
        $echange->delete();
        return redirect()->route('echanges.index')->with('success', 'Échange supprimé.');
    }
}