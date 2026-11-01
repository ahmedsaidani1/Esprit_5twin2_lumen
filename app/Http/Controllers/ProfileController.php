<?php

namespace App\Http\Controllers;

use App\Models\Communaute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Page "Mon profil". Les mises à jour (informations, photo, mot de passe, 2FA)
 * passent par les routes Fortify ; ce contrôleur affiche la page et gère la suppression du compte.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'communautes' => Communaute::orderBy('nom')->get(),
        ]);
    }

    /** Envoie vers la confirmation du mot de passe de Fortify, puis revient sur la section 2FA du profil. */
    public function confirm()
    {
        redirect()->setIntendedUrl(route('profile.edit').'#2fa');

        return redirect()->route('password.confirm');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Les échanges (et leurs factures) sont des pièces comptables : on ne les supprime pas.
        if ($user->aDesEchanges()) {
            return back()->withErrors(['password' => 'Votre compte a des échanges et ne peut pas être supprimé.'], 'deleteAccount');
        }

        // On ne laisse pas la plateforme sans administrateur.
        if ($user->isAdmin() && User::role('admin')->count() === 1) {
            return back()->withErrors(['password' => 'Vous êtes le dernier administrateur : nommez-en un autre avant.'], 'deleteAccount');
        }

        Auth::logout();

        if ($user->photo_path) {
            Storage::disk('local')->delete($user->photo_path);
        }
        $user->tokens()->delete();
        $user->delete(); // installations, productions, offres, compteurs et relevés suivent (clés étrangères en cascade)

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Votre compte a été supprimé.');
    }
}
