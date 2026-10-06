<?php

namespace App\Policies;

use App\Models\Production;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Une production appartient au propriétaire de son installation. */
class ProductionPolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Production $production): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Production $production): bool
    {
        return $production->installation->user_id === $user->id;
    }

    public function delete(User $user, Production $production): bool
    {
        return $production->installation->user_id === $user->id;
    }
}
