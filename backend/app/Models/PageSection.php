<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    use HasTranslations {
        getAttribute as translatedAttribute;
    }

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['eyebrow', 'title', 'subtitle', 'content', 'button_label', 'image_alt'];

    /** Text-like layout settings that can also be translated, stored as "settings:<key>". */
    public const TRANSLATABLE_SETTINGS = ['card_title', 'card_text', 'badge_label'];

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

    public function translatableFields(): array
    {
        return [...$this->translatable, ...array_map(fn ($k) => 'settings:'.$k, self::TRANSLATABLE_SETTINGS)];
    }

    /** Overlays translated text settings (card title, badge label...) for the current locale. */
    public function getAttribute($key)
    {
        $value = $this->translatedAttribute($key);

        if ($key === 'settings' && is_array($value) && app()->getLocale() !== config('cms.default_locale')
            && $this->relationLoaded('translations')) {
            foreach ($this->getRelation('translations') as $t) {
                if ($t->locale === app()->getLocale() && str_starts_with($t->field, 'settings:') && filled($t->value)) {
                    $setting = substr($t->field, 9);
                    if (in_array($setting, self::TRANSLATABLE_SETTINGS, true) && array_key_exists($setting, $value)) {
                        $value[$setting] = $t->value;
                    }
                }
            }
        }

        return $value;
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
