<?php

namespace App\Http\Requests\Admin;

class GalleryAlbumRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'slug' => $this->slug('gallery_albums'),
            'description' => 'nullable|string|max:500',
            'cover_image' => $this->media(),
            'event_date' => 'nullable|date',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:200',
            'images.*.path' => ['required', ...$this->media()],
            'images.*.alt' => 'nullable|string|max:255',
        ];
    }
}
