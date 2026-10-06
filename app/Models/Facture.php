<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    public const STATUTS = ['payee', 'impayee', 'annulee'];

    protected $fillable = [
        'echange_id', 'montant_total', 'date_emission', 'date_echeance', 'statut',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
    ];

    public function echange()
    {
        return $this->belongsTo(Echange::class);
    }
}