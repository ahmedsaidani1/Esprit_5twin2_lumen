<?php

namespace App\Notifications;

use App\Models\Echange;
use Illuminate\Notifications\Notification;

/** Membre A — notification à l'acheteur : le vendeur a accepté, refusé ou terminé l'échange. */
class StatutEchangeModifie extends Notification
{
    public const LIBELLES = ['accepte' => 'accepté', 'refuse' => 'refusé', 'termine' => 'terminé', 'propose' => 'remis en proposition'];

    public function __construct(public Echange $echange) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => sprintf('%s a %s votre échange n° %d (%s kWh).',
                $this->echange->vendeur->name, self::LIBELLES[$this->echange->statut] ?? $this->echange->statut,
                $this->echange->id, $this->echange->quantite_kwh),
            'lien' => route('echanges.show', $this->echange),
            'type' => $this->echange->statut,
        ];
    }
}
