<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TestimonialResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'designation' => $this->designation,
            'company' => $this->company,
            'quote' => $this->quote,
            'photo' => $this->photo,
            'photo_url' => $this->url($this->photo),
            'rating' => $this->rating,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
