<?php

namespace App\Services;

use App\Models\Echange;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Contrôles d'accès qui dépendent des données envoyées (et non d'un enregistrement existant).
 * Ils lèvent des erreurs de validation, donc affichées sous le champ concerné (web) ou en 422 (API).
 */
class AccessService
{
    /** Production / offre : l'installation choisie doit appartenir à l'utilisateur. */
    public function assertInstallation(User $user, int|string|null $installationId): void
    {
        if ($user->isAdmin() || ! $installationId) {
            return;
        }

        if (! Installation::whereKey($installationId)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'installation_id' => 'Vous ne pouvez agir que sur vos propres installations.',
            ]);
        }
    }

    /** Installation : un membre est toujours son propre propriétaire. */
    public function forceOwner(User $user, array $data): array
    {
        if (! $user->isAdmin()) {
            $data['user_id'] = $user->id;
        }

        return $data;
    }

    /** Échange : l'utilisateur doit être le vendeur ou l'acheteur. */
    public function assertEchangeParticipant(User $user, array $data): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (! in_array($user->id, [(int) $data['vendeur_id'], (int) $data['acheteur_id']], true)) {
            throw ValidationException::withMessages([
                'acheteur_id' => 'Vous devez être le vendeur ou l\'acheteur de l\'échange.',
            ]);
        }
    }

    /** Échange : seul le vendeur accepte, refuse ou termine ; l'acheteur ne fait que proposer. */
    public function assertCanSetStatut(User $user, string $statut, array $data, ?Echange $existing = null): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $actuel = $existing?->statut ?? 'propose';
        $vendeurId = (int) ($existing?->vendeur_id ?? $data['vendeur_id']);

        if ($statut !== $actuel && $user->id !== $vendeurId) {
            throw ValidationException::withMessages([
                'statut' => 'Seul le vendeur peut accepter, refuser ou terminer un échange.',
            ]);
        }
    }

    /** Facture : seul le vendeur de l'échange (l'émetteur) peut la créer. */
    public function assertFactureIssuer(User $user, int|string|null $echangeId): void
    {
        if ($user->isAdmin() || ! $echangeId) {
            return;
        }

        if (! Echange::whereKey($echangeId)->where('vendeur_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'echange_id' => 'Seul le vendeur de l\'échange peut émettre sa facture.',
            ]);
        }
    }
}
