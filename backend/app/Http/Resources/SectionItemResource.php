<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SectionItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'value' => $this->value,
            'icon' => $this->icon,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'link_url' => $this->link_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
