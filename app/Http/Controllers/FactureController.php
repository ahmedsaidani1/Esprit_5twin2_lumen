<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FactureController extends Controller
{
    private function rules(?Facture $facture = null): array
    {
        return [
            'echange_id'    => ['required', 'exists:echanges,id',
                                Rule::unique('factures', 'echange_id')->ignore($facture?->id)],
            'montant_total' => 'nullable|numeric|min:0',
            'date_emission' => 'required|date',
            'date_echeance' => 'required|date|after_or_equal:date_emission',
            'statut'        => 'required|in:' . implode(',', Facture::STATUTS),
        ];
    }

    public function index(Request $request)
    {
        $factures = Facture::with('echange.vendeur', 'echange.acheteur')
            ->filtre($request->query())->latest()->paginate(10)->withQueryString();
        return view('factures.index', compact('factures'));
    }

    public function create()
    {
        return view('factures.create', ['echanges' => Echange::with('vendeur', 'acheteur')->get()]);
    }

    public function store(Request $request)
    {
        Facture::create(Facture::completerMontant($request->validate($this->rules())));
        return redirect()->route('factures.index')->with('success', 'Facture créée avec succès.');
    }

    public function show(Facture $facture)
    {
        $facture->load('echange.vendeur', 'echange.acheteur', 'echange.communaute');
        return view('factures.show', compact('facture'));
    }

    public function edit(Facture $facture)
    {
        return view('factures.edit', [
            'facture' => $facture,
            'echanges' => Echange::with('vendeur', 'acheteur')->get(),
        ]);
    }

    public function update(Request $request, Facture $facture)
    {
        $facture->update(Facture::completerMontant($request->validate($this->rules($facture))));
        return redirect()->route('factures.index')->with('success', 'Facture modifiée avec succès.');
    }

    public function destroy(Facture $facture)
    {
        $facture->delete();
        return redirect()->route('factures.index')->with('success', 'Facture supprimée.');
    }
}
