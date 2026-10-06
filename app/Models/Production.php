<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = ['installation_id', 'date', 'energie_produite_kwh'];

    protected $casts = ['date' => 'date'];

    public function installation()
    {
        return $this->belongsTo(Installation::class);
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['installation_id'] ?? null, fn ($q, $v) => $q->where('installation_id', $v))
            ->when($f['du'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($f['au'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v));
    }
}
