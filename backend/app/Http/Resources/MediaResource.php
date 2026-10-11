<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MediaResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'url' => $this->url($this->path),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'folder' => $this->folder,
            'created_at' => $this->date($this->created_at),
        ];
    }
}
