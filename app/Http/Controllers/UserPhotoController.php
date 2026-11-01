<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** Sert les photos de profil (stockées hors du dossier public) aux utilisateurs connectés. */
class UserPhotoController extends Controller
{
    public function __invoke(User $user)
    {
        abort_unless($user->photo_path && Storage::disk('local')->exists($user->photo_path), 404);

        return Storage::disk('local')->response($user->photo_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
