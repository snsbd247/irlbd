<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory, HasSlug, Publishable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['title', 'hero_title', 'hero_subtitle', 'content', 'meta_title', 'meta_description'];

    public const TEMPLATES = ['sections', 'products', 'news', 'contact', 'gallery', 'careers'];

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

    protected static function booted(): void
    {
        static::deleting(function (self $page) {
            $sectionIds = $page->sections()->pluck('id');
            Translation::forget('SectionItem', SectionItem::whereIn('page_section_id', $sectionIds)->pluck('id'));
            Translation::forget('PageSection', $sectionIds);
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publicPath(): string
    {
        return $this->is_home ? '/' : '/'.$this->slug;
    }
}
