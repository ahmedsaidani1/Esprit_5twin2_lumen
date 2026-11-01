<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Gestion des utilisateurs, réservée aux administrateurs (voir UserPolicy). */
class UserController extends Controller
{
    private function rules(?User $user = null): array
    {
        return [
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role'          => 'required|in:' . implode(',', User::ROLES),
            'communaute_id' => 'nullable|exists:communautes,id',
            'password'      => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('communaute', 'roles')->withCount('installations')
            ->filtre($request->query())->orderBy('name')->paginate(10)->withQueryString();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);
        return view('users.create', ['communautes' => Communaute::orderBy('nom')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $data = $request->validate($this->rules());
        $user = User::create($data);
        $user->forceFill(['email_verified_at' => now()])->save(); // compte créé par un admin : e-mail considéré vérifié
        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $user->load('communaute', 'installations')->loadCount(['echangesVendus', 'echangesAchetes']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', ['user' => $user, 'communautes' => Communaute::orderBy('nom')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate($this->rules($user));

        // Un administrateur ne peut pas se retirer lui-même le rôle admin (risque de se verrouiller dehors).
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'Vous ne pouvez pas retirer votre propre rôle administrateur.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Les échanges et leurs factures sont des pièces comptables : on ne les supprime pas en cascade.
        if ($user->aDesEchanges()) {
            return back()->withErrors(['user' => 'Impossible de supprimer : cet utilisateur a des échanges.']);
        }

        if ($user->photo_path) {
            Storage::disk('local')->delete($user->photo_path);
        }
        $user->delete(); // installations, productions, offres, compteurs et relevés suivent en cascade
        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }
}
