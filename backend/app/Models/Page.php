<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory, HasSlug, Publishable;

    public const TEMPLATES = ['sections', 'products', 'news', 'contact', 'gallery'];

    protected $fillable = [
        'title', 'slug', 'template', 'hero_title', 'hero_subtitle', 'hero_image', 'content',
        'status', 'is_home', 'published_at', 'meta_title', 'meta_description', 'meta_keywords', 'og_image',
    ];

    protected $casts = [
        'content' => SanitizedHtml::class,
        'is_home' => 'boolean',
        'is_system' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publicPath(): string
    {
        return $this->is_home ? '/' : '/'.$this->slug;
    }
}
