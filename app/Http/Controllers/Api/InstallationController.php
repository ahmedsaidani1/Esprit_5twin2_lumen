<?php

namespace App\Http\Controllers\Api;

use App\Models\Installation;
use App\Services\AccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InstallationController extends CrudController
{
    protected string $model = Installation::class;

    protected array $with = ['proprietaire:id,name', 'communaute'];

    public function __construct(private AccessService $access) {}

    protected function rules(?Model $record = null): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'communaute_id' => 'nullable|exists:communautes,id',
            'nom' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', Installation::TYPES),
            'puissance_kw' => 'required|numeric|min:0.01',
            'localisation' => 'nullable|string|max:255',
        ];
    }

    /** Un membre est toujours le propriétaire de ses installations. */
    protected function prepare(Request $request): void
    {
        $request->merge($this->access->forceOwner($request->user(), $request->all()));
    }
}
