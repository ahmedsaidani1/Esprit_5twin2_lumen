<?php

namespace App\Services;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;

/** Membre A — fonctionnalité avancée : génération de la facture au format PDF (dompdf). */
class FacturePdfService
{
    public function nomFichier(Facture $facture): string
    {
        return sprintf('facture-%05d.pdf', $facture->id);
    }

    public function contenu(Facture $facture): string
    {
        $facture->loadMissing('echange.vendeur', 'echange.acheteur', 'echange.communaute', 'echange.offre.installation');

        return Pdf::loadView('factures.pdf', ['facture' => $facture])->setPaper('a4')->output();
    }
}
