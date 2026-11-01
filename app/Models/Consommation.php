<?php

namespace App\Models;

use Database\Factories\ConsommationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consommation extends Model
{
    /** @use HasFactory<ConsommationFactory> */
    use HasFactory;

    protected $fillable = ['compteur_id', 'date', 'energie_consommee_kwh'];

    protected $casts = ['date' => 'date'];

    public function compteur(): BelongsTo
    {
        return $this->belongsTo(Compteur::class);
    }

    /** Chacun ne voit que les relevés de ses compteurs ; l'admin voit tout. */
    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->whereHas('compteur', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['compteur_id'] ?? null, fn ($q, $v) => $q->where('compteur_id', $v))
            ->when($f['du'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($f['au'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v));
    }
}
