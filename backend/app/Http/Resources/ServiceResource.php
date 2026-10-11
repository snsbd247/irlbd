<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ServiceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
