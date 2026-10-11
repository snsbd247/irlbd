<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductCategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->whenCounted('products'),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
        ];
    }
}
