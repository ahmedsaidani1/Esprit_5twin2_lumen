<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\Installation;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    public function __construct(private AccessService $access)
    {
    }

    private function rules(): array
    {
        return [
            'user_id'      => 'required|exists:users,id',
            'communaute_id' => 'nullable|exists:communautes,id',
            'nom'          => 'required|string|max:255',
            'type'         => 'required|in:' . implode(',', Installation::TYPES),
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

    public function show(Installation $installation)
    {
        $this->authorize('view', $installation);
        $installation->load('proprietaire', 'communaute', 'productions', 'offres');
        return view('installations.show', compact('installation'));
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
