<?php

namespace App\Http\Controllers\Api;

use App\Models\Consommation;
use App\Models\User;
use App\Services\ConsommationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConsommationController extends CrudController
{
    protected string $model = Consommation::class;

    protected array $with = ['compteur:id,reference'];

    public function __construct(private ConsommationService $service)
    {
    }

    protected function rules(?Model $record = null): array
    {
        return $this->service->rules($record);
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
}
