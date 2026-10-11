<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->role?->is_super;
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        if (! $this->role) {
            return [];
        }

        if ($this->role->is_super) {
            return Permission::query()->pluck('slug')->all();
        }

        return $this->role->permissions->pluck('slug')->all();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || ! $this->role) {
            return false;
        }

        if ($this->role->is_super) {
            return true;
        }

        return $this->role->permissions->contains('slug', $permission);
    }
}
