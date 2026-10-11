<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PageResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'path' => $this->publicPath(),
            'template' => $this->template,
            'hero_title' => $this->hero_title,
            'hero_subtitle' => $this->hero_subtitle,
            'hero_image' => $this->hero_image,
            'hero_image_url' => $this->url($this->hero_image),
            'content' => $this->content,
            'is_home' => $this->is_home,
            'status' => $this->when($this->isAdmin($request), $this->status),
            'is_system' => $this->when($this->isAdmin($request), $this->is_system),
            'published_at' => $this->date($this->published_at),
            'updated_at' => $this->date($this->updated_at),
            'sections_count' => $this->whenCounted('sections'),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_image' => $this->og_image,
            'og_image_url' => $this->url($this->og_image),
            'sections' => PageSectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
