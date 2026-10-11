<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RolePolicy extends ModulePolicy
{
    protected string $module = 'users';

    /** Super roles can only be changed by super admins (prevents privilege escalation). */
    public function update(User $user, Model $model): bool
    {
        return $this->allowed($user) && (! $model->is_super || $user->isSuperAdmin());
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model) && ! $model->is_super && $model->users()->doesntExist();
    }
}
