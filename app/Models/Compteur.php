<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compteur extends Model
{
    /** @use HasFactory<\Database\Factories\CompteurFactory> */
    use HasFactory;

    public const STATUTS = ['actif', 'inactif', 'en_panne'];

    protected $fillable = ['user_id', 'installation_id', 'reference', 'date_installation', 'statut'];

    protected $casts = ['date_installation' => 'date'];

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }

    public function consommations()
    {
        return $this->hasMany(Consommation::class);
    }

    /** Chacun ne voit que ses compteurs ; l'admin voit tout. */
    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($f['installation_id'] ?? null, fn ($q, $v) => $q->where('installation_id', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('reference', 'like', "%{$v}%"));
    }
}
