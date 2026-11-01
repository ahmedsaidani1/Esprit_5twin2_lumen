<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD JSON générique. Chaque ressource indique son modèle, ses relations
 * à charger et ses règles de validation ; la logique métier reste dans les services
 * et les autorisations dans les policies (les mêmes que pour l'interface web).
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Relations chargées dans les réponses. */
    protected array $with = [];

    abstract protected function rules(?Model $record = null): array;

    protected function persist(array $data, User $actor): Model
    {
        return $this->model::create($data);
    }

    protected function modify(Model $record, array $data, User $actor): Model
    {
        $record->update($data);

        return $record;
    }

    protected function remove(Model $record): ?JsonResponse
    {
        $record->delete();

        return null;
    }

    protected function beforeIndex(): void {}

    /** Ajuste la requête avant validation (ex. imposer le propriétaire). */
    protected function prepare(Request $request): void {}

    /** Restreint la liste aux enregistrements visibles par l'utilisateur. */
    protected function scopeFor(Builder $query, User $user): Builder
    {
        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', $this->model);
        $this->beforeIndex();

        $query = $this->scopeFor($this->model::with($this->with), $request->user());

        $page = $query->filtre($request->query())
            ->latest('id')->paginate(min($request->integer('per_page', 15), 100))->withQueryString();

        return response()->json($page);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->model::with($this->with)->findOrFail($id);
        $this->authorize('view', $record);

        return response()->json($record);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', $this->model);
        $this->prepare($request);
        $record = $this->persist($request->validate($this->rules()), $request->user());

        return response()->json($record->load($this->with), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);
        $this->authorize('update', $record);
        $this->prepare($request);
        $record = $this->modify($record, $request->validate($this->rules($record)), $request->user());

        return response()->json($record->load($this->with));
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);
        $this->authorize('delete', $record);

        return $this->remove($record) ?? response()->json(null, 204);
    }
}
