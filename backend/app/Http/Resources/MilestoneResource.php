<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MilestoneResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
