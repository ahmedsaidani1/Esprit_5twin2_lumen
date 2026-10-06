<?php

namespace App\Http\Controllers\Api;

use App\Models\Facture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class FactureController extends CrudController
{
    protected string $model = Facture::class;

    protected array $with = ['echange'];

    protected function rules(?Model $record = null): array
    {
        return [
            'echange_id'    => ['required', 'exists:echanges,id',
                                Rule::unique('factures', 'echange_id')->ignore($record?->id)],
            'montant_total' => 'nullable|numeric|min:0',
            'date_emission' => 'required|date',
            'date_echeance' => 'required|date|after_or_equal:date_emission',
            'statut'        => 'required|in:' . implode(',', Facture::STATUTS),
        ];
    }

    protected function persist(array $data): Model
    {
        return Facture::create(Facture::completerMontant($data));
    }

    protected function modify(Model $record, array $data): Model
    {
        $record->update(Facture::completerMontant($data));
        return $record;
    }
}
