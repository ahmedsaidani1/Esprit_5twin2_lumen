<?php

namespace App\Policies;

use App\Models\Communaute;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Les communautés sont visibles par tous mais gérées par les administrateurs. */
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
        return false;
    }

    public function update(User $user, Communaute $communaute): bool
    {
        return false;
    }

    public function delete(User $user, Communaute $communaute): bool
    {
        return false;
    }
}
