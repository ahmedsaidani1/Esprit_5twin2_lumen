<?php

namespace App\Policies;

use App\Models\Echange;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Un échange n'est visible et modifiable que par ses deux participants. */
class EchangePolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true; // la liste est filtrée sur les échanges du membre
    }

    public function view(User $user, Echange $echange): bool
    {
        return $this->participant($user, $echange);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Echange $echange): bool
    {
        return $this->participant($user, $echange);
    }

    public function delete(User $user, Echange $echange): bool
    {
        return $this->participant($user, $echange);
    }

    private function participant(User $user, Echange $echange): bool
    {
        return in_array($user->id, [$echange->vendeur_id, $echange->acheteur_id], true);
    }
}
