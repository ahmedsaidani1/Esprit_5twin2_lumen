<?php

namespace App\Policies;

use App\Models\Offre;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Les offres sont publiques ; seul le propriétaire de l'installation les modifie. */
class OffrePolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Offre $offre): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Offre $offre): bool
    {
        return $offre->installation->user_id === $user->id;
    }

    public function delete(User $user, Offre $offre): bool
    {
        return $offre->installation->user_id === $user->id;
    }
}
