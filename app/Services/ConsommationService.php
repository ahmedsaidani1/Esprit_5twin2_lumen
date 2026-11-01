<?php

namespace App\Services;

use App\Models\Compteur;
use App\Models\Consommation;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Règles des relevés de consommation, partagées par l'interface web, l'API et l'import CSV. */
class ConsommationService
{
    public function rules(?Consommation $consommation = null): array
    {
        return [
            'compteur_id'           => 'required|exists:compteurs,id',
            'date'                  => 'required|date|before_or_equal:today',
            'energie_consommee_kwh' => 'required|numeric|min:0|max:100000',
        ];
    }

    public function create(array $data, User $actor): Consommation
    {
        $this->checkCompteur($actor, $data);
        return Consommation::create($data);
    }

    public function update(Consommation $consommation, array $data, User $actor): Consommation
    {
        $this->checkCompteur($actor, $data, $consommation);
        $consommation->update($data);
        return $consommation;
    }

    /** Le compteur doit être à l'utilisateur, et "actif" pour recevoir un nouveau relevé. */
    public function checkCompteur(User $actor, array $data, ?Consommation $existing = null): void
    {
        $compteur = Compteur::findOrFail($data['compteur_id']);

        if (!$actor->isAdmin() && $compteur->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'compteur_id' => 'Vous ne pouvez saisir des relevés que sur vos propres compteurs.',
            ]);
        }

        // Corriger un relevé existant reste possible ; en ajouter un exige un compteur actif.
        $nouveauSurCeCompteur = !$existing || $existing->compteur_id !== $compteur->id;
        if ($nouveauSurCeCompteur && $compteur->statut !== 'actif') {
            throw ValidationException::withMessages([
                'compteur_id' => "Ce compteur est « {$compteur->statut} » : il ne peut pas recevoir de nouveaux relevés.",
            ]);
        }

        // Un seul relevé par compteur et par jour (whereDate : fiable quel que soit le format stocké)
        $doublon = Consommation::where('compteur_id', $compteur->id)
            ->whereDate('date', $data['date'])
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->exists();
        if ($doublon) {
            throw ValidationException::withMessages([
                'date' => 'Un relevé existe déjà pour ce compteur à cette date.',
            ]);
        }
    }
}
