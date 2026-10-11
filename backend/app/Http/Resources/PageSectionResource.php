<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageSectionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'type' => $this->type,
            'admin_label' => $this->when($this->isAdmin($request), $this->admin_label),
            'eyebrow' => $this->eyebrow,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->content,
            'image' => $this->image,
            'image_url' => $this->url($this->image),
            'image_alt' => $this->image_alt,
            'video_url' => $this->video_url,
            'button_label' => $this->button_label,
            'button_url' => $this->button_url,
            'settings' => (object) ($this->settings ?? []),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'items' => SectionItemResource::collection($this->whenLoaded('items')),
            // Data from other modules, attached by SectionHydrator for public pages.
            'data' => $this->when(isset($this->resource->hydrated), fn () => $this->resource->hydrated),
        ];
    }
}
