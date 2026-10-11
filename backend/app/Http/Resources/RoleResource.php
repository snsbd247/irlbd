<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RoleResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_super' => $this->is_super,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('slug')),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
