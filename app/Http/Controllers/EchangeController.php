<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Offre;
use App\Models\User;
use App\Services\EchangeService;
use Illuminate\Http\Request;

class EchangeController extends Controller
{
    public function __construct(private EchangeService $service)
    {
    }

    private function formData(?Echange $echange = null): array
    {
        return [
            'users' => User::orderBy('name')->get(),
            'communautes' => Communaute::orderBy('nom')->get(),
            'offres' => Offre::with('installation')
                ->where('statut', 'disponible')
                ->when($echange?->offre_id, fn ($q, $id) => $q->orWhere('id', $id))
                ->get(),
        ];
    }

    public function index(Request $request)
    {
        $echanges = Echange::with(['vendeur', 'acheteur', 'communaute', 'offre', 'facture'])
            ->filtre($request->query())
            ->latest('date_echange')->paginate(10)->withQueryString();

        return view('echanges.index', [
            'echanges' => $echanges,
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('echanges.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->service->create($request->validate($this->service->rules()));
        return redirect()->route('echanges.index')->with('success', 'Échange créé avec succès.');
    }

    public function show(Echange $echange)
    {
        $echange->load(['vendeur', 'acheteur', 'communaute', 'offre.installation', 'facture']);
        return view('echanges.show', compact('echange'));
    }

    public function edit(Echange $echange)
    {
        return view('echanges.edit', ['echange' => $echange] + $this->formData($echange));
    }

    public function update(Request $request, Echange $echange)
    {
        $this->service->update($echange, $request->validate($this->service->rules()));
        return redirect()->route('echanges.index')->with('success', 'Échange modifié avec succès.');
    }

    public function destroy(Echange $echange)
    {
        $this->service->delete($echange);
        return redirect()->route('echanges.index')->with('success', 'Échange supprimé.');
    }
}
