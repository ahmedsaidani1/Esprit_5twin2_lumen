<?php

namespace App\Policies;

use App\Models\Communaute;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Les communautés sont visibles par tous ; les gérer demande la permission Spatie "gerer-communautes". */
class CommunautePolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Communaute $communaute): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('gerer-communautes');
    }

    public function update(User $user, Communaute $communaute): bool
    {
        return $user->can('gerer-communautes');
    }

    public function delete(User $user, Communaute $communaute): bool
    {
        return $user->can('gerer-communautes');
    }
}
