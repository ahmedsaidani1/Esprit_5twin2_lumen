<?php

namespace App\Http\Controllers\Api;

use App\Models\Production;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Database\Eloquent\Model;

class ProductionController extends CrudController
{
    protected string $model = Production::class;

    protected array $with = ['installation:id,nom'];

    public function __construct(private AccessService $access)
    {
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'installation_id'      => 'required|exists:installations,id',
            'date'                 => 'required|date',
            'energie_produite_kwh' => 'required|numeric|min:0',
        ];
    }

    protected function persist(array $data, User $actor): Model
    {
        $this->access->assertInstallation($actor, $data['installation_id']);
        return parent::persist($data, $actor);
    }

    protected function modify(Model $record, array $data, User $actor): Model
    {
        $this->access->assertInstallation($actor, $data['installation_id']);
        return parent::modify($record, $data, $actor);
    }
}
