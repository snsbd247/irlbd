<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['label'];

    public const LOCATIONS = ['header', 'footer_quick', 'footer_products'];

    protected $fillable = ['location', 'parent_id', 'label', 'link_type', 'page_id', 'url', 'open_in_new_tab', 'sort_order', 'is_active'];

    protected $casts = ['open_in_new_tab' => 'boolean', 'is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function resolvedUrl(): ?string
    {
        if ($this->link_type === 'page') {
            return $this->page ? $this->page->publicPath() : null;
        }

        return $this->url ?: '#';
    }
}
