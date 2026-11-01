<?php

namespace App\Models;

use Database\Factories\CommunauteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Communaute extends Model
{
    /** @use HasFactory<CommunauteFactory> */
    use HasFactory;

    protected $fillable = [
        'nom',
        'ville',
    ];

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class);
    }

    public function echanges(): HasMany
    {
        return $this->hasMany(Echange::class);
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query->when($f['q'] ?? null, fn ($q, $v) => $q->where(
            fn ($w) => $w->where('nom', 'like', "%{$v}%")->orWhere('ville', 'like', "%{$v}%")
        ));
    }
}
