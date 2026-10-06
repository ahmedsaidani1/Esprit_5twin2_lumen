<?php

namespace App\Policies;

use App\Models\Facture;
use App\Models\User;
use App\Policies\Concerns\AdminBypass;

/** Une facture est visible par les deux participants de l'échange, gérée par le vendeur (l'émetteur). */
class FacturePolicy
{
    use AdminBypass;

    public function viewAny(User $user): bool
    {
        return true; // la liste est filtrée sur les factures du membre
    }

    public function view(User $user, Facture $facture): bool
    {
        return in_array($user->id, [$facture->echange->vendeur_id, $facture->echange->acheteur_id], true);
    }

    public function create(User $user): bool
    {
        return true; // le vendeur de l'échange est vérifié à l'enregistrement
    }

    public function update(User $user, Facture $facture): bool
    {
        return $facture->echange->vendeur_id === $user->id;
    }

    public function delete(User $user, Facture $facture): bool
    {
        return $facture->echange->vendeur_id === $user->id;
    }
}
