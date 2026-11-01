<?php

namespace App\Http\Controllers\Api;

use App\Models\Echange;
use App\Models\User;
use App\Services\EchangeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class EchangeController extends CrudController
{
    protected string $model = Echange::class;

    protected array $with = ['vendeur:id,name', 'acheteur:id,name', 'communaute', 'offre', 'facture'];

    public function __construct(private EchangeService $service) {}

    protected function rules(?Model $record = null): array
    {
        return $this->service->rules();
    }

    protected function scopeFor(Builder $query, User $user): Builder
    {
        return $query->visiblePour($user);
    }

    protected function persist(array $data, User $actor): Model
    {
        return $this->service->create($data, $actor);
    }

    protected function modify(Model $record, array $data, User $actor): Model
    {
        return $this->service->update($record, $data, $actor);
    }

    protected function remove(Model $record): ?JsonResponse
    {
        $this->service->delete($record);

        return null;
    }
}
