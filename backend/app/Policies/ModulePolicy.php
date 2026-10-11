<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Grants every CRUD ability when the user holds "{module}.manage".
 * Each concrete policy only declares which permission module it guards.
 */
abstract class ModulePolicy
{
    protected string $module;

    protected function allowed(User $user): bool
    {
        return $user->hasPermission($this->module.'.manage');
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }
}
