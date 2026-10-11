<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NewsCategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'posts_count' => $this->whenCounted('posts'),
        ];
    }
}
