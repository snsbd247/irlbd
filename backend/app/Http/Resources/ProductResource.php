<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'is_featured' => $this->is_featured,
            'status' => $this->when($this->isAdmin($request), $this->status),
            'sort_order' => $this->sort_order,
            'product_category_id' => $this->product_category_id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => [
                'id' => $i->id, 'path' => $i->path, 'url' => $this->url($i->path), 'alt' => $i->alt,
            ])),
            'specifications' => $this->whenLoaded('specifications', fn () => $this->specifications->map(fn ($s) => [
                'id' => $s->id, 'label' => $s->label, 'value' => $s->value,
            ])),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'updated_at' => $this->date($this->updated_at),
        ];
    }
}
