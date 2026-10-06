<?php

namespace App\Http\Controllers\Api;

use App\Models\Installation;
use Illuminate\Database\Eloquent\Model;

class InstallationController extends CrudController
{
    protected string $model = Installation::class;

    protected array $with = ['proprietaire:id,name', 'communaute'];

    protected function rules(?Model $record = null): array
    {
        return [
            'user_id'       => 'required|exists:users,id',
            'communaute_id' => 'nullable|exists:communautes,id',
            'nom'           => 'required|string|max:255',
            'type'          => 'required|in:' . implode(',', Installation::TYPES),
            'puissance_kw'  => 'required|numeric|min:0.01',
            'localisation'  => 'nullable|string|max:255',
        ];
    }
}
