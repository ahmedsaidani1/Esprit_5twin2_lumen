<?php

namespace App\Policies;

use App\Models\Installation;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Visible par tous (marché de l'énergie) ; modifiable seulement par son propriétaire. */
class InstallationPolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Installation $installation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Installation $installation): bool
    {
        return $installation->user_id === $user->id;
    }

    public function delete(User $user, Installation $installation): bool
    {
        return $installation->user_id === $user->id;
    }
}
