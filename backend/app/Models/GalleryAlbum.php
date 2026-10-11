<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use HasSlug, Sortable;

    protected $fillable = ['title', 'slug', 'description', 'cover_image', 'event_date', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'event_date' => 'date'];

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
