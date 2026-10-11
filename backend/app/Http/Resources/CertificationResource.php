<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CertificationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'logo_url' => $this->url($this->logo),
            'description' => $this->description,
            'is_verified' => $this->is_verified,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
