<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    use HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['eyebrow', 'title', 'subtitle', 'content', 'button_label', 'image_alt'];

    protected $fillable = [
        'page_id', 'type', 'admin_label', 'eyebrow', 'title', 'subtitle', 'content', 'image', 'image_alt',
        'video_url', 'button_label', 'button_url', 'settings', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'content' => SanitizedHtml::class,
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(fn (self $section) => Translation::forget('SectionItem', $section->items()->pluck('id')));
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SectionItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
