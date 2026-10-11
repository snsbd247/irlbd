<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OfficeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
