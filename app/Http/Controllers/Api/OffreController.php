<?php

namespace App\Http\Controllers\Api;

use App\Models\Offre;
use App\Models\User;
use App\Services\OffreService;
use Illuminate\Database\Eloquent\Model;

class OffreController extends CrudController
{
    protected string $model = Offre::class;

    protected array $with = ['installation:id,nom,user_id'];

    public function __construct(private OffreService $service) {}

    protected function rules(?Model $record = null): array
    {
        return $this->service->rules();
    }

    protected function beforeIndex(): void
    {
        Offre::expireOutdated();
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
