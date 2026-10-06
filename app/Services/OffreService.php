<?php

namespace App\Services;

use App\Models\Installation;
use App\Models\Offre;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OffreService
{
    public function rules(): array
    {
        return [
            'installation_id' => 'required|exists:installations,id',
            'quantite_kwh'    => 'required|numeric|min:0.01',
            'prix_kwh'        => 'required|numeric|min:0',
            'date_debut'      => 'required|date',
            'date_fin'        => 'required|date|after_or_equal:date_debut',
            'statut'          => 'required|in:' . implode(',', Offre::STATUTS),
        ];
    }

    /** La quantité offerte ne peut pas dépasser l'énergie réellement produite et non engagée. */
    public function checkStock(array $data, ?Offre $offre = null): void
    {
        // Une offre expirée n'engage plus d'énergie : pas de contrôle de stock.
        if (($data['statut'] ?? null) === 'expiree') {
            return;
        }

        $stock = Installation::findOrFail($data['installation_id'])->stockDisponible($offre?->id);

        if ((float) $data['quantite_kwh'] > $stock) {
            throw ValidationException::withMessages([
                'quantite_kwh' => 'Stock insuffisant : seulement ' . number_format($stock, 2)
                    . ' kWh produits et non engagés pour cette installation.',
            ]);
        }
    }

    public function __construct(private AccessService $access)
    {
    }

    public function create(array $data, User $actor): Offre
    {
        $this->access->assertInstallation($actor, $data['installation_id']);
        $this->checkStock($data);
        return Offre::create($data);
    }

    public function update(Offre $offre, array $data, User $actor): Offre
    {
        $this->access->assertInstallation($actor, $data['installation_id']);
        $this->checkStock($data, $offre);
        $offre->update($data);
        return $offre;
    }
}
