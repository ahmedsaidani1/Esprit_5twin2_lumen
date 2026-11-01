<?php

namespace App\Policies;

use App\Models\Consommation;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Un relevé appartient au propriétaire de son compteur. */
class ConsommationPolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true; // la liste est filtrée sur les relevés du membre
    }

    public function view(User $user, Consommation $consommation): bool
    {
        return $consommation->compteur->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('gerer-compteurs'); // le compteur choisi est vérifié par ConsommationService
    }

    public function update(User $user, Consommation $consommation): bool
    {
        return $consommation->compteur->user_id === $user->id;
    }

    public function delete(User $user, Consommation $consommation): bool
    {
        return $consommation->compteur->user_id === $user->id;
    }
}
