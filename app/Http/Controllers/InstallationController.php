<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
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

    public function index(Request $request)
    {
        $installations = Installation::with('proprietaire', 'communaute')
            ->filtre($request->query())->latest()->paginate(10)->withQueryString();
        return view('installations.index', compact('installations'));
    }

    public function create()
    {
        return view('installations.create', [
            'users' => User::orderBy('name')->get(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Installation::create($request->validate($this->rules()));
        return redirect()->route('installations.index')->with('success', 'Installation créée avec succès.');
    }

    public function show(Installation $installation)
    {
        $installation->load('proprietaire', 'communaute', 'productions', 'offres');
        return view('installations.show', compact('installation'));
    }

    public function edit(Installation $installation)
    {
        return view('installations.edit', [
            'installation' => $installation,
            'users' => User::orderBy('name')->get(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Installation $installation)
    {
        $installation->update($request->validate($this->rules()));
        return redirect()->route('installations.index')->with('success', 'Installation modifiée avec succès.');
    }

    public function destroy(Installation $installation)
    {
        $installation->delete();
        return redirect()->route('installations.index')->with('success', 'Installation supprimée.');
    }
}
