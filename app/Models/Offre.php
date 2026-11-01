<?php

namespace App\Models;

use Database\Factories\OffreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offre extends Model
{
    /** @use HasFactory<OffreFactory> */
    use HasFactory;

    public const STATUTS = ['disponible', 'reservee', 'expiree'];

    protected $fillable = [
        'installation_id', 'quantite_kwh', 'prix_kwh', 'date_debut', 'date_fin', 'statut',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function echanges(): HasMany
    {
        return $this->hasMany(Echange::class);
    }

    public function getMontantAttribute()
    {
        return $this->quantite_kwh * $this->prix_kwh;
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($f['installation_id'] ?? null, fn ($q, $v) => $q->where('installation_id', $v));
    }

    /** Passe en "expiree" les offres disponibles dont la date de fin est dépassée. */
    public static function expireOutdated(): int
    {
        return static::where('statut', 'disponible')
            ->whereDate('date_fin', '<', today())
            ->update(['statut' => 'expiree']);
    }
}
