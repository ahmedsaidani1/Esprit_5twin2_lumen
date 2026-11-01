<?php

namespace App\Http\Controllers;

use App\Models\Compteur;
use App\Models\Installation;
use App\Models\User;
use App\Services\CompteurService;
use App\Services\Ia\AnalyseConsommationService;
use Illuminate\Http\Request;

class CompteurController extends Controller
{
    public function __construct(private CompteurService $service)
    {
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            // un membre ne choisit pas le propriétaire : c'est lui
            'users' => $user->isAdmin() ? User::orderBy('name')->get() : collect([$user]),
            'installations' => Installation::gereesPar($user)->orderBy('nom')->get(),
        ];
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Compteur::class);

        $compteurs = Compteur::visiblePour($request->user())
            ->with('proprietaire', 'installation')->withCount('consommations')
            ->filtre($request->query())->orderBy('reference')->paginate(10)->withQueryString();

        return view('compteurs.index', [
            'compteurs' => $compteurs,
            'installations' => Installation::gereesPar($request->user())->orderBy('nom')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Compteur::class);
        return view('compteurs.create', $this->formData($request));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Compteur::class);
        $request->merge($this->service->prepare($request->user(), $request->all()));

        $compteur = $this->service->create($request->validate($this->service->rules()));
        return redirect()->route('compteurs.show', $compteur)->with('success', 'Compteur créé avec succès.');
    }

    public function show(Compteur $compteur, AnalyseConsommationService $ia)
    {
        $this->authorize('view', $compteur);
        $compteur->load('proprietaire', 'installation');

        return view('compteurs.show', [
            'compteur' => $compteur,
            'releves' => $compteur->consommations()->latest('date')->limit(10)->get(),
            'serie' => $ia->serie($compteur),
            'anomalies' => $ia->anomalies($compteur),
            'prevision' => $ia->prevoir($compteur),
        ]);
    }

    public function edit(Request $request, Compteur $compteur)
    {
        $this->authorize('update', $compteur);
        return view('compteurs.edit', ['compteur' => $compteur] + $this->formData($request));
    }

    public function update(Request $request, Compteur $compteur)
    {
        $this->authorize('update', $compteur);
        $request->merge($this->service->prepare($request->user(), $request->all()));

        $this->service->update($compteur, $request->validate($this->service->rules($compteur)));
        return redirect()->route('compteurs.index')->with('success', 'Compteur modifié avec succès.');
    }

    public function destroy(Compteur $compteur)
    {
        $this->authorize('delete', $compteur);
        $this->service->delete($compteur);
        return redirect()->route('compteurs.index')->with('success', 'Compteur supprimé.');
    }
}
