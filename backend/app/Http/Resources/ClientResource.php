<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ClientResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'logo_url' => $this->url($this->logo),
            'website_url' => $this->website_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
