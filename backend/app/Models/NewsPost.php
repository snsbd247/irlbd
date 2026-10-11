<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPost extends Model
{
    use HasSlug, Publishable;

    protected $fillable = [
        'news_category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'is_featured', 'published_at', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'content' => SanitizedHtml::class,
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
