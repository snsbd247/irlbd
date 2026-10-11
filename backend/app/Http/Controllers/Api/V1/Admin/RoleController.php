<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RoleController extends CrudController
{
    protected string $model = Role::class;

    protected string $resource = RoleResource::class;

    protected string $request = RoleRequest::class;

    protected array $searchable = ['name'];

    protected array $with = ['permissions'];

    protected array $withCount = ['users'];

    protected array $order = ['id', 'asc'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        $data = collect($data)->except('permissions')->all();

        if (! $model) {
            $slug = Str::slug($data['name']) ?: 'role';
            $data['slug'] = Role::where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(4)) : $slug;
        }

        return $data;
    }

    protected function afterSave(Model $model, FormRequest $request): void
    {
        if (! $model->is_super) {
            $ids = Permission::whereIn('slug', $request->validated('permissions') ?? [])->pluck('id');
            $model->permissions()->sync($ids);
        }
    }
}
