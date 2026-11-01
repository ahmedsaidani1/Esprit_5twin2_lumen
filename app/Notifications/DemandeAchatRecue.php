<?php

namespace App\Notifications;

use App\Models\Echange;
use Illuminate\Notifications\Notification;

/** Membre A — notification au vendeur : un acheteur propose un échange. */
class DemandeAchatRecue extends Notification
{
    public function __construct(public Echange $echange)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => sprintf('%s souhaite vous acheter %s kWh à %s DT/kWh.',
                $this->echange->acheteur->name, $this->echange->quantite_kwh, $this->echange->prix_unitaire),
            'lien' => route('echanges.show', $this->echange),
            'type' => 'demande',
        ];
    }
}
