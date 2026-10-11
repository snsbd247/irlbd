<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['path', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt', 'folder', 'uploaded_by'];

    public function getUrlAttribute(): ?string
    {
        return MediaUrl::url($this->path);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
