<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion des utilisateurs : permission Spatie "gerer-utilisateurs" (rôle admin).
 * Pas de bypass global, pour qu'un admin ne puisse pas supprimer son propre compte d'ici.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer-utilisateurs');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('gerer-utilisateurs');
    }

    public function create(User $user): bool
    {
        return $user->can('gerer-utilisateurs');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('gerer-utilisateurs');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('gerer-utilisateurs') && $user->id !== $model->id;
    }
}
