<?php

namespace App\Http\Requests\Admin;


class NewsPostRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'news_category_id' => 'nullable|integer|exists:news_categories,id',
            'title' => 'required|string|max:255',
            'slug' => $this->slug('news_posts'),
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string|max:200000',
            'featured_image' => $this->media(),
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'published_at' => 'nullable|date',
            ...$this->seo(),
        ];
    }
}
