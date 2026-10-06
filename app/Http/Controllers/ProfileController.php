<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Profil de l'utilisateur connecté : il ne modifie que son propre compte, jamais son rôle. */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'communaute_id'    => 'nullable|exists:communautes,id',
            'password'         => 'nullable|string|min:8|confirmed',
            'current_password' => 'required_with:password|nullable|current_password',
        ]);

        unset($data['current_password']);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Profil mis à jour.');
    }
}
