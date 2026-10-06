<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Communaute extends Model
{
    protected $fillable = [
        'nom',
        'ville',
    ];

    public function installations()
    {
        return $this->hasMany(Installation::class);
    }

    public function echanges()
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
