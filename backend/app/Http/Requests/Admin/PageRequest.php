<?php

namespace App\Http\Requests\Admin;


class PageRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'slug' => $this->slug('pages'),
            'template' => 'required|in:'.implode(',', \App\Models\Page::TEMPLATES),
            'hero_title' => 'nullable|string|max:200',
            'hero_subtitle' => 'nullable|string|max:500',
            'hero_image' => $this->media(),
            'content' => 'nullable|string|max:200000',
            'status' => 'required|in:draft,published',
            'is_home' => 'boolean',
            'published_at' => 'nullable|date',
            'meta_keywords' => 'nullable|string|max:255',
            'og_image' => $this->media(),
            ...$this->seo(),
        ];
    }
}
