<?php

namespace App\Models;

use Database\Factories\EchangeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Echange extends Model
{
    /** @use HasFactory<EchangeFactory> */
    use HasFactory;

    public const STATUTS = ['propose', 'accepte', 'refuse', 'termine'];

    protected $fillable = [
        'vendeur_id', 'acheteur_id', 'communaute_id', 'offre_id',
        'quantite_kwh', 'prix_unitaire', 'date_echange', 'statut',
    ];

    protected $casts = ['date_echange' => 'datetime'];

    public function vendeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function acheteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function communaute(): BelongsTo
    {
        return $this->belongsTo(Communaute::class);
    }

    public function offre(): BelongsTo
    {
        return $this->belongsTo(Offre::class);
    }

    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }

    public function getMontantAttribute()
    {
        return $this->quantite_kwh * $this->prix_unitaire;
    }

    /** Un membre ne voit que les échanges où il est vendeur ou acheteur ; l'admin voit tout. */
    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->where(fn ($q) => $q->where('vendeur_id', $user->id)->orWhere('acheteur_id', $user->id));
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($f['communaute_id'] ?? null, fn ($q, $v) => $q->where('communaute_id', $v))
            ->when($f['vendeur_id'] ?? null, fn ($q, $v) => $q->where('vendeur_id', $v))
            ->when($f['acheteur_id'] ?? null, fn ($q, $v) => $q->where('acheteur_id', $v))
            ->when($f['offre_id'] ?? null, fn ($q, $v) => $q->where('offre_id', $v));
    }
}
