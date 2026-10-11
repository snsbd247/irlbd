<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'two_factor_enabled' => $this->hasTwoFactor(),
            'role_id' => $this->role_id,
            'role' => new RoleResource($this->whenLoaded('role')),
            'last_login_at' => $this->date($this->last_login_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
