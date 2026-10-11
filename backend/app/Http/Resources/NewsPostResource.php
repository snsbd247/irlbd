<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NewsPostResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($this->withContent($request), $this->content),
            'featured_image' => $this->featured_image,
            'featured_image_url' => $this->url($this->featured_image),
            'status' => $this->when($this->isAdmin($request), $this->status),
            'is_featured' => $this->is_featured,
            'published_at' => $this->date($this->published_at),
            'news_category_id' => $this->news_category_id,
            'category' => new NewsCategoryResource($this->whenLoaded('category')),
            'author' => $this->when($this->isAdmin($request), fn () => $this->author?->name),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'updated_at' => $this->date($this->updated_at),
        ];
    }

    /** Full content only on the detail endpoint or in the admin, not in list payloads. */
    protected function withContent(Request $request): bool
    {
        return $this->isAdmin($request) || $request->route('newsPost') !== null || $request->route('slug') !== null;
    }
}
