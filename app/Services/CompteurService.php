<?php

namespace App\Services;

use App\Models\Compteur;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Règles des compteurs, partagées par l'interface web et l'API. */
class CompteurService
{
    public function __construct(private AccessService $access)
    {
    }

    public function rules(?Compteur $compteur = null): array
    {
        return [
            'user_id'           => 'required|exists:users,id',
            'installation_id'   => 'nullable|exists:installations,id',
            'reference'         => ['required', 'string', 'max:50', Rule::unique('compteurs', 'reference')->ignore($compteur?->id)],
            'date_installation' => 'required|date|before_or_equal:today',
            'statut'            => 'required|in:' . implode(',', Compteur::STATUTS),
        ];
    }

    /** Un membre est toujours le propriétaire de ses compteurs (même logique que les installations). */
    public function prepare(User $actor, array $input): array
    {
        return $this->access->forceOwner($actor, $input);
    }

    public function create(array $data): Compteur
    {
        $this->checkInstallation($data);
        return Compteur::create($data);
    }

    public function update(Compteur $compteur, array $data): Compteur
    {
        $this->checkInstallation($data);
        $compteur->update($data);
        return $compteur;
    }

    /** Un compteur avec des relevés n'est pas supprimé : on perdrait l'historique de consommation. */
    public function delete(Compteur $compteur): void
    {
        if ($compteur->consommations()->exists()) {
            throw ValidationException::withMessages([
                'compteur' => 'Impossible de supprimer : ce compteur a des relevés. Passez-le en « inactif ».',
            ]);
        }
        $compteur->delete();
    }

    /** L'installation éventuelle doit appartenir au propriétaire du compteur. */
    private function checkInstallation(array $data): void
    {
        if (empty($data['installation_id'])) {
            return;
        }

        if (!Installation::whereKey($data['installation_id'])->where('user_id', $data['user_id'])->exists()) {
            throw ValidationException::withMessages([
                'installation_id' => 'L\'installation doit appartenir au propriétaire du compteur.',
            ]);
        }
    }
}
