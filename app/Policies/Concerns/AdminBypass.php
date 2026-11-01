<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Un utilisateur ayant la permission Spatie "voir-toutes-les-donnees" (le rôle admin)
 * peut tout faire ; les autres passent par les méthodes de la policy.
 */
trait AdminBypass
{
    public function before(User $user): ?bool
    {
        return $user->hasPermissionTo('voir-toutes-les-donnees') ? true : null;
    }
}
