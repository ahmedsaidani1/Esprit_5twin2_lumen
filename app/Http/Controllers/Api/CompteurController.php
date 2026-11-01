<?php

namespace App\Http\Controllers\Api;

use App\Models\Compteur;
use App\Models\User;
use App\Services\CompteurService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteurController extends CrudController
{
    protected string $model = Compteur::class;

    protected array $with = ['installation:id,nom'];

    public function __construct(private CompteurService $service)
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

    protected function prepare(Request $request): void
    {
        $request->merge($this->service->prepare($request->user(), $request->all()));
    }

    protected function persist(array $data, User $actor): Model
    {
        return $this->service->create($data);
    }

    protected function modify(Model $record, array $data, User $actor): Model
    {
        return $this->service->update($record, $data);
    }

    protected function remove(Model $record): ?JsonResponse
    {
        $this->service->delete($record);
        return null;
    }
}
