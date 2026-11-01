<?php

namespace App\Notifications;

use App\Models\Facture;
use App\Services\FacturePdfService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Membre A — l'acheteur reçoit sa facture : notification dans l'application + e-mail avec le PDF joint. */
class FactureEmise extends Notification
{
    public function __construct(public Facture $facture)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $f = $this->facture;
        $pdf = app(FacturePdfService::class);

        return (new MailMessage)
            ->subject(sprintf('Votre facture SolarShare n° %05d', $f->id))
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line(sprintf('%s vous a émis une facture de %s DT pour %s kWh d\'énergie renouvelable.',
                $f->echange->vendeur->name, number_format($f->montant_total, 2, ',', ' '), $f->echange->quantite_kwh))
            ->line('Échéance de paiement : ' . $f->date_echeance->format('d/m/Y') . '.')
            ->action('Voir la facture', route('factures.show', $f))
            ->line('La facture est jointe à cet e-mail au format PDF.')
            ->attachData($pdf->contenu($f), $pdf->nomFichier($f), ['mime' => 'application/pdf']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => sprintf('Nouvelle facture n° %05d de %s DT, échéance le %s.',
                $this->facture->id, number_format($this->facture->montant_total, 2, ',', ' '), $this->facture->date_echeance->format('d/m/Y')),
            'lien' => route('factures.show', $this->facture),
            'type' => 'facture',
        ];
    }
}
