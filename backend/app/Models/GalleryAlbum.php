<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    use HasSlug, Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['title', 'description'];

    protected $fillable = ['title', 'slug', 'description', 'cover_image', 'event_date', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'event_date' => 'date'];

    protected static function booted(): void
    {
        static::deleting(fn (self $album) => Translation::forget('GalleryImage', $album->images()->pluck('id')));
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
