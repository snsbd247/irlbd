<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends ModulePolicy
{
    protected string $module = 'users';

    /** Only super admins may change other super admins. */
    public function update(User $user, Model $model): bool
    {
        if (! $this->allowed($user)) {
            return false;
        }

        return ! $model->isSuperAdmin() || $user->isSuperAdmin();
    }

    /** Nobody can delete their own account, and only super admins can delete super admins. */
    public function delete(User $user, Model $model): bool
    {
        return $user->id !== $model->id && $this->update($user, $model);
    }
}
