<?php

namespace App\Http\Controllers\Api;

use App\Models\Communaute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class CommunauteController extends CrudController
{
    protected string $model = Communaute::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'nom' => 'required|string|max:255',
            'ville' => 'nullable|string|max:255',
        ];
    }

    protected function remove(Model $record): ?JsonResponse
    {
        if ($record->echanges()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer : cette communauté a des échanges.'], 409);
        }

        return parent::remove($record);
    }
}
