<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
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

    private function withMontant(array $data): array
    {
        // Si le montant est vide, on le calcule : quantité × prix unitaire
        if (!isset($data['montant_total'])) {
            $data['montant_total'] = Echange::findOrFail($data['echange_id'])->montant;
        }
        return $data;
    }

    public function index()
    {
        $factures = Facture::with('echange.vendeur', 'echange.acheteur')->latest()->paginate(10);
        return view('factures.index', compact('factures'));
    }

    public function create()
    {
        return view('factures.create', ['echanges' => Echange::with('vendeur', 'acheteur')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->withMontant($request->validate($this->rules()));
        Facture::create($data);
        return redirect()->route('factures.index')->with('success', 'Facture créée avec succès.');
    }

    public function show(Facture $facture)
    {
        $facture->load('echange.vendeur', 'echange.acheteur', 'echange.communaute');
        return view('factures.show', compact('facture'));
    }

    public function downloadPdf(Facture $facture)
    {
        $facture->load('echange.vendeur', 'echange.acheteur', 'echange.communaute');

        return Pdf::loadView('factures.pdf', compact('facture'))
            ->setPaper('a4')
            ->download('facture-' . $facture->id . '.pdf');
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
        $data = $this->withMontant($request->validate($this->rules($facture)));
        $facture->update($data);
        return redirect()->route('factures.index')->with('success', 'Facture modifiée avec succès.');
    }

    public function destroy(Facture $facture)
    {
        $facture->delete();
        return redirect()->route('factures.index')->with('success', 'Facture supprimée.');
    }
}
