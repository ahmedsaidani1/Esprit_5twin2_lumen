<?php

namespace App\Http\Controllers\Api;

use App\Models\Production;
use Illuminate\Database\Eloquent\Model;

class ProductionController extends CrudController
{
    protected string $model = Production::class;

    protected array $with = ['installation:id,nom'];

    protected function rules(?Model $record = null): array
    {
        return [
            'installation_id'      => 'required|exists:installations,id',
            'date'                 => 'required|date',
            'energie_produite_kwh' => 'required|numeric|min:0',
        ];
    }
}
