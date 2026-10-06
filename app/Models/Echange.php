<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Echange extends Model
{
    public const STATUTS = ['propose', 'accepte', 'refuse', 'termine'];

    protected $fillable = [
        'vendeur_id', 'acheteur_id', 'communaute_id',
        'quantite_kwh', 'prix_unitaire', 'date_echange', 'statut',
    ];

    protected $casts = ['date_echange' => 'datetime'];

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function communaute()
    {
        return $this->belongsTo(Communaute::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    public function getMontantAttribute()
    {
        return $this->quantite_kwh * $this->prix_unitaire;
    }
}