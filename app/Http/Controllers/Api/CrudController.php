<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD JSON générique. Chaque ressource indique son modèle, ses relations
 * à charger et ses règles de validation ; la logique métier reste dans les services.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Relations chargées dans les réponses. */
    protected array $with = [];

    abstract protected function rules(?Model $record = null): array;

    protected function persist(array $data): Model
    {
        return $this->model::create($data);
    }

    protected function modify(Model $record, array $data): Model
    {
        $record->update($data);
        return $record;
    }

    protected function remove(Model $record): ?JsonResponse
    {
        $record->delete();
        return null;
    }

    protected function beforeIndex(): void
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->beforeIndex();

        $page = $this->model::with($this->with)->filtre($request->query())
            ->latest('id')->paginate(min($request->integer('per_page', 15), 100))->withQueryString();

        return response()->json($page);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->model::with($this->with)->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $record = $this->persist($request->validate($this->rules()));

        return response()->json($record->load($this->with), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);
        $record = $this->modify($record, $request->validate($this->rules($record)));

        return response()->json($record->load($this->with));
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->model::findOrFail($id);

        return $this->remove($record) ?? response()->json(null, 204);
    }
}
