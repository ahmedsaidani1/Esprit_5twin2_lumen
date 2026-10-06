<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Production;
use App\Services\AccessService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(private AccessService $access)
    {
    }

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
        $this->authorize('viewAny', Production::class);

        $productions = Production::with('installation')->filtre($request->query())
            ->latest('date')->paginate(10)->withQueryString();
        return view('productions.index', [
            'productions' => $productions,
            'installations' => Installation::orderBy('nom')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Production::class);
        return view('productions.create', ['installations' => Installation::gereesPar($request->user())->orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Production::class);
        $data = $request->validate($this->rules());
        $this->access->assertInstallation($request->user(), $data['installation_id']);

        Production::create($data);
        return redirect()->route('productions.index')->with('success', 'Production enregistrée avec succès.');
    }

    public function show(Production $production)
    {
        $this->authorize('view', $production);
        $production->load('installation.proprietaire');
        return view('productions.show', compact('production'));
    }

    public function edit(Request $request, Production $production)
    {
        $this->authorize('update', $production);
        return view('productions.edit', [
            'production' => $production,
            'installations' => Installation::gereesPar($request->user())->orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Production $production)
    {
        $this->authorize('update', $production);
        $data = $request->validate($this->rules());
        $this->access->assertInstallation($request->user(), $data['installation_id']);

        $production->update($data);
        return redirect()->route('productions.index')->with('success', 'Production modifiée avec succès.');
    }

    public function destroy(Production $production)
    {
        $this->authorize('delete', $production);
        $production->delete();
        return redirect()->route('productions.index')->with('success', 'Production supprimée.');
    }
}
