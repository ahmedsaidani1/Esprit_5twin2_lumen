<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Offre;
use App\Services\OffreService;
use Illuminate\Http\Request;

class OffreController extends Controller
{
    public function __construct(private OffreService $service)
    {
    }

    public function index(Request $request)
    {
        Offre::expireOutdated();

        $offres = Offre::with('installation')->filtre($request->query())
            ->latest()->paginate(10)->withQueryString();

        return view('offres.index', [
            'offres' => $offres,
            'installations' => Installation::orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('offres.create', ['installations' => Installation::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $this->service->create($request->validate($this->service->rules()));
        return redirect()->route('offres.index')->with('success', 'Offre créée avec succès.');
    }

    public function show(Offre $offre)
    {
        $offre->load('installation.proprietaire');
        return view('offres.show', compact('offre'));
    }

    public function edit(Offre $offre)
    {
        return view('offres.edit', [
            'offre' => $offre,
            'installations' => Installation::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Offre $offre)
    {
        $this->service->update($offre, $request->validate($this->service->rules()));
        return redirect()->route('offres.index')->with('success', 'Offre modifiée avec succès.');
    }

    public function destroy(Offre $offre)
    {
        $offre->delete();
        return redirect()->route('offres.index')->with('success', 'Offre supprimée.');
    }
}
