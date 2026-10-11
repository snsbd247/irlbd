<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UserController extends CrudController
{
    protected string $model = User::class;

    protected string $resource = UserResource::class;

    protected string $request = UserRequest::class;

    protected array $searchable = ['name', 'email'];

    protected array $filters = ['role' => 'role_id', 'is_active' => 'is_active'];

    protected array $with = ['role'];

    protected array $order = ['id', 'asc'];

    protected array $sortableColumns = ['id', 'name', 'email', 'created_at', 'last_login_at'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        $actor = $request->user();
        $role = Role::find($data['role_id']);

        // Only super admins can grant super-admin access.
        if ($role?->is_super && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages(['role_id' => 'Only a super admin can assign this role.']);
        }

        // Prevent locking yourself out.
        if ($model && $model->id === $actor->id) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
            }
            if ($actor->isSuperAdmin() && ! $role?->is_super && User::whereHas('role', fn ($q) => $q->where('is_super', true))->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'At least one super admin must remain.']);
            }
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
