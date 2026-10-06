<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    public const TYPES = ['solaire', 'eolien', 'hydraulique', 'autre'];

    protected $fillable = ['user_id', 'communaute_id', 'nom', 'type', 'puissance_kw', 'localisation'];

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function communaute()
    {
        return $this->belongsTo(Communaute::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    public function offres()
    {
        return $this->hasMany(Offre::class);
    }

    /**
     * Énergie encore proposable à la vente : total produit moins les quantités
     * déjà engagées dans des offres disponibles ou réservées.
     */
    public function stockDisponible(?int $ignoreOffreId = null): float
    {
        $produit = (float) $this->productions()->sum('energie_produite_kwh');
        $engage = (float) $this->offres()
            ->whereIn('statut', ['disponible', 'reservee'])
            ->when($ignoreOffreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->sum('quantite_kwh');

        return max(0, $produit - $engage);
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['communaute_id'] ?? null, fn ($q, $v) => $q->where('communaute_id', $v))
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('nom', 'like', "%{$v}%"));
    }
}
