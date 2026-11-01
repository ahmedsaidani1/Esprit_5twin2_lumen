<?php

namespace App\Policies;

use App\Models\Compteur;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Un compteur est privé : seul son propriétaire le voit et le gère. */
class CompteurPolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true; // la liste est filtrée sur les compteurs du membre
    }

    public function view(User $user, Compteur $compteur): bool
    {
        return $compteur->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('gerer-compteurs');
    }

    public function update(User $user, Compteur $compteur): bool
    {
        return $compteur->user_id === $user->id;
    }

    public function delete(User $user, Compteur $compteur): bool
    {
        return $compteur->user_id === $user->id;
    }
}
