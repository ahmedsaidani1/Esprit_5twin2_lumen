<?php

namespace App\Http\Controllers;

use App\Models\Echange;
use App\Models\Facture;
use App\Services\AccessService;
use App\Services\EchangeService;
use App\Services\FacturePdfService;
use App\Services\Ia\RisqueImpayeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FactureController extends Controller
{
    public function __construct(
        private AccessService $access,
        private RisqueImpayeService $risque,
    ) {}

    private function rules(?Facture $facture = null): array
    {
        return [
            'echange_id' => ['required', 'exists:echanges,id',
                Rule::unique('factures', 'echange_id')->ignore($facture?->id)],
            'montant_total' => 'nullable|numeric|min:0',
            'date_emission' => 'required|date',
            'date_echeance' => 'required|date|after_or_equal:date_emission',
            'statut' => 'required|in:'.implode(',', Facture::STATUTS),
        ];
    }

    /** Échanges pour lesquels l'utilisateur peut émettre une facture (ceux dont il est vendeur ; tous pour l'admin). */
    private function echangesFacturables(Request $request)
    {
        return Echange::with('vendeur', 'acheteur')
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('vendeur_id', $request->user()->id))
            ->get();
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Facture::class);

        $factures = Facture::visiblePour($request->user())->with('echange.vendeur', 'echange.acheteur')
            ->filtre($request->query())->latest()->paginate(10)->withQueryString();

        // IA : risque d'impayé des factures en attente, montré au vendeur (et à l'admin), pas à l'acheteur
        $risques = $factures->getCollection()
            ->filter(fn ($f) => $f->statut === 'impayee' && $request->user()->can('update', $f))
            ->mapWithKeys(fn ($f) => [$f->id => $this->risque->predire($f)])->filter();

        return view('factures.index', compact('factures', 'risques'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Facture::class);

        return view('factures.create', ['echanges' => $this->echangesFacturables($request)]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Facture::class);
        $data = $request->validate($this->rules());
        $this->access->assertFactureIssuer($request->user(), $data['echange_id']);

        $facture = Facture::create(Facture::completerMontant($data));
        app(EchangeService::class)->notifierFacture($facture);

        return redirect()->route('factures.index')->with('success', "Facture créée et envoyée à l'acheteur.");
    }

    public function show(Facture $facture)
    {
        $this->authorize('view', $facture);
        $facture->load('echange.vendeur', 'echange.acheteur', 'echange.communaute');

        return view('factures.show', [
            'facture' => $facture,
            'risque' => $facture->statut === 'impayee' && request()->user()->can('update', $facture) ? $this->risque->predire($facture) : null,
            'modele' => $this->risque->informations(),
        ]);
    }

    public function edit(Request $request, Facture $facture)
    {
        $this->authorize('update', $facture);

        return view('factures.edit', [
            'facture' => $facture,
            'echanges' => $this->echangesFacturables($request),
        ]);
    }

    public function update(Request $request, Facture $facture)
    {
        $this->authorize('update', $facture);
        $data = $request->validate($this->rules($facture));
        $this->access->assertFactureIssuer($request->user(), $data['echange_id']);

        $facture->update(Facture::completerMontant($data));

        return redirect()->route('factures.index')->with('success', 'Facture modifiée avec succès.');
    }

    public function destroy(Facture $facture)
    {
        $this->authorize('delete', $facture);
        $facture->delete();

        return redirect()->route('factures.index')->with('success', 'Facture supprimée.');
    }

    /** Membre A — téléchargement de la facture en PDF. */
    public function pdf(Facture $facture, FacturePdfService $pdf)
    {
        $this->authorize('view', $facture);

        return response($pdf->contenu($facture), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nomFichier($facture).'"',
        ]);
    }

    /** Membre A — renvoie la facture par e-mail à l'acheteur (PDF joint). */
    public function envoyer(Facture $facture)
    {
        $this->authorize('update', $facture);
        app(EchangeService::class)->notifierFacture($facture);

        return back()->with('success', 'Facture envoyée par e-mail à '.$facture->echange->acheteur->email.'.');
    }
}
