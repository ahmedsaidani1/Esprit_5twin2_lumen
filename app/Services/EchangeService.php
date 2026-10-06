<?php

namespace App\Services;

use App\Models\Echange;
use App\Models\Facture;
use App\Models\Offre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier des échanges, partagées par l'interface web et l'API.
 */
class EchangeService
{
    public function rules(): array
    {
        return [
            'vendeur_id'    => 'required|exists:users,id',
            'acheteur_id'   => 'required|exists:users,id|different:vendeur_id',
            'communaute_id' => 'required|exists:communautes,id',
            'offre_id'      => 'nullable|exists:offres,id',
            'quantite_kwh'  => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0',
            'date_echange'  => 'required|date',
            'statut'        => 'required|in:' . implode(',', Echange::STATUTS),
        ];
    }

    public function create(array $data): Echange
    {
        $this->checkOffre($data);

        return DB::transaction(function () use ($data) {
            $echange = Echange::create($data);
            $this->syncOffre($echange);
            $this->syncFacture($echange);
            return $echange;
        });
    }

    public function update(Echange $echange, array $data): Echange
    {
        $this->checkOffre($data, $echange);

        return DB::transaction(function () use ($echange, $data) {
            $ancienne = $echange->offre_id;
            $echange->update($data);
            $this->syncOffre($echange, $ancienne);
            $this->syncFacture($echange);
            return $echange;
        });
    }

    public function delete(Echange $echange): void
    {
        DB::transaction(function () use ($echange) {
            $offreId = $echange->offre_id;
            $echange->delete();
            if ($offreId) {
                Offre::whereKey($offreId)->where('statut', 'reservee')->update(['statut' => 'disponible']);
            }
        });
    }

    /** Vérifie la cohérence entre l'échange et l'offre choisie. */
    private function checkOffre(array $data, ?Echange $echange = null): void
    {
        if (empty($data['offre_id'])) {
            return;
        }

        $offre = Offre::with('installation')->findOrFail($data['offre_id']);
        $dejaLiee = $echange && $echange->offre_id === $offre->id;

        if ($offre->statut !== 'disponible' && !$dejaLiee) {
            throw ValidationException::withMessages(['offre_id' => "Cette offre n'est plus disponible."]);
        }
        if ((int) $data['vendeur_id'] !== (int) $offre->installation->user_id) {
            throw ValidationException::withMessages(['vendeur_id' => "Le vendeur doit être le propriétaire de l'installation de l'offre."]);
        }
        if ((float) $data['quantite_kwh'] > (float) $offre->quantite_kwh) {
            throw ValidationException::withMessages(['quantite_kwh' => "La quantité dépasse celle de l'offre ({$offre->quantite_kwh} kWh)."]);
        }
    }

    /** Réserve l'offre liée (ou la libère si l'échange est refusé / délié). */
    private function syncOffre(Echange $echange, ?int $ancienneOffreId = null): void
    {
        if ($ancienneOffreId && $ancienneOffreId !== $echange->offre_id) {
            Offre::whereKey($ancienneOffreId)->where('statut', 'reservee')->update(['statut' => 'disponible']);
        }
        if ($echange->offre_id) {
            Offre::whereKey($echange->offre_id)->where('statut', '!=', 'expiree')
                ->update(['statut' => $echange->statut === 'refuse' ? 'disponible' : 'reservee']);
        }
    }

    /**
     * Facture automatique : un échange accepté ou terminé reçoit sa facture ;
     * un échange refusé annule sa facture si elle n'est pas déjà payée.
     */
    private function syncFacture(Echange $echange): void
    {
        $facture = $echange->facture()->first();

        if (in_array($echange->statut, ['accepte', 'termine'], true) && !$facture) {
            Facture::create([
                'echange_id'    => $echange->id,
                'montant_total' => $echange->montant,
                'date_emission' => today(),
                'date_echeance' => today()->addDays(30),
                'statut'        => 'impayee',
            ]);
        } elseif ($echange->statut === 'refuse' && $facture && $facture->statut === 'impayee') {
            $facture->update(['statut' => 'annulee']);
        }
    }
}
