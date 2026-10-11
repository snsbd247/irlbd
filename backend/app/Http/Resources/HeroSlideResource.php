<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class HeroSlideResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eyebrow' => $this->eyebrow,
            'title' => $this->title,
            'highlight' => $this->highlight,
            'title_suffix' => $this->title_suffix,
            'subtitle' => $this->subtitle,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'primary_label' => $this->primary_label,
            'primary_url' => $this->primary_url,
            'secondary_label' => $this->secondary_label,
            'secondary_url' => $this->secondary_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
