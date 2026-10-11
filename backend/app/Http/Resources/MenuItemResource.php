<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MenuItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'parent_id' => $this->parent_id,
            'label' => $this->label,
            'link_type' => $this->link_type,
            'page_id' => $this->page_id,
            'url' => $this->url,
            'href' => $this->resolvedUrl(),
            'open_in_new_tab' => $this->open_in_new_tab,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'children' => MenuItemResource::collection($this->whenLoaded('children')),
        ];
    }
}
