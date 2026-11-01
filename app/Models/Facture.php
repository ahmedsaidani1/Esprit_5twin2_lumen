<?php

namespace App\Models;

use Database\Factories\FactureFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facture extends Model
{
    /** @use HasFactory<FactureFactory> */
    use HasFactory;

    public const STATUTS = ['payee', 'impayee', 'annulee'];

    protected $fillable = [
        'echange_id', 'montant_total', 'date_emission', 'date_echeance', 'statut',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
    ];

    public function echange(): BelongsTo
    {
        return $this->belongsTo(Echange::class);
    }

    /** Si le montant est vide, on le calcule : quantité × prix unitaire de l'échange. */
    public static function completerMontant(array $data): array
    {
        if (! isset($data['montant_total'])) {
            $data['montant_total'] = Echange::findOrFail($data['echange_id'])->montant;
        }

        return $data;
    }

    /** Un membre ne voit que les factures de ses propres échanges ; l'admin voit tout. */
    public function scopeVisiblePour(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereHas('echange', fn ($q) => $q->visiblePour($user));
    }

    public function scopeFiltre(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($f['echange_id'] ?? null, fn ($q, $v) => $q->where('echange_id', $v));
    }
}
