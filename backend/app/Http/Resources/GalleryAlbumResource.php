<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GalleryAlbumResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->cover_image ?: ($this->relationLoaded('images') ? $this->images->first()?->path : null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_image' => $this->cover_image,
            'cover_image_url' => $this->url($cover),
            'event_date' => $this->event_date?->toDateString(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'images_count' => $this->whenCounted('images'),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => [
                'id' => $i->id, 'path' => $i->path, 'url' => $this->url($i->path), 'alt' => $i->caption, 'caption' => $i->caption,
            ])),
        ];
    }
}
