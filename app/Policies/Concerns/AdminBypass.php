<?php

namespace App\Policies\Concerns;

use App\Models\User;

/** Un administrateur peut tout faire ; les autres passent par les méthodes de la policy. */
trait AdminBypass
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }
}
