<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\Installation;
use App\Models\User;
use App\Services\AccessService;
use App\Services\Ia\PrevisionProductionService;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    public function __construct(private AccessService $access) {}

    private function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'communaute_id' => 'nullable|exists:communautes,id',
            'nom' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', Installation::TYPES),
            'puissance_kw' => 'required|numeric|min:0.01',
            'localisation' => 'nullable|string|max:255',
        ];
    }

    /** Un membre est toujours le propriétaire de ses installations ; seul l'admin choisit le propriétaire. */
    private function owners(Request $request)
    {
        return $request->user()->isAdmin() ? User::orderBy('name')->get() : collect([$request->user()]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Installation::class);

        $installations = Installation::with('proprietaire', 'communaute')
            ->filtre($request->query())->latest()->paginate(10)->withQueryString();

        return view('installations.index', compact('installations'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Installation::class);

        return view('installations.create', [
            'users' => $this->owners($request),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Installation::class);
        $request->merge($this->access->forceOwner($request->user(), $request->all()));

        Installation::create($request->validate($this->rules()));

        return redirect()->route('installations.index')->with('success', 'Installation créée avec succès.');
    }

    public function show(Request $request, Installation $installation, PrevisionProductionService $ia)
    {
        $this->authorize('view', $installation);
        $installation->load('proprietaire', 'communaute', 'offres');

        return view('installations.show', [
            'installation' => $installation,
            'totalProduit' => (float) $installation->productions()->sum('energie_produite_kwh'),
            'dernieresProductions' => $installation->productions()->latest('date')->limit(10)->get(),
            'prevision' => $ia->prevoir($installation),
            // la suggestion d'offre ne concerne que le propriétaire (ou l'admin)
            'suggestion' => $request->user()->can('update', $installation) ? $ia->suggererOffre($installation) : null,
        ]);
    }

    public function edit(Request $request, Installation $installation)
    {
        $this->authorize('update', $installation);

        return view('installations.edit', [
            'installation' => $installation,
            'users' => $this->owners($request),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Installation $installation)
    {
        $this->authorize('update', $installation);
        $request->merge($this->access->forceOwner($request->user(), $request->all()));

        $installation->update($request->validate($this->rules()));

        return redirect()->route('installations.index')->with('success', 'Installation modifiée avec succès.');
    }

    public function destroy(Installation $installation)
    {
        $this->authorize('delete', $installation);
        $installation->delete();

        return redirect()->route('installations.index')->with('success', 'Installation supprimée.');
    }
}
