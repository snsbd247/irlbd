<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasSlug, Publishable, Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'short_description', 'description', 'meta_title', 'meta_description'];

    protected string $slugFrom = 'name';

    protected $fillable = [
        'product_category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'image', 'datasheet',
        'is_featured', 'status', 'sort_order', 'meta_title', 'meta_description',
    ];

    protected $casts = ['is_featured' => 'boolean', 'description' => SanitizedHtml::class];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        // Specs and variants are removed by the FK cascade; clear their translations too.
        static::deleting(function (self $product) {
            Translation::forget('ProductSpecification', $product->specifications()->pluck('id'));
            Translation::forget('ProductVariant', $product->variants()->pluck('id'));
        });
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->orderBy('sort_order')->orderBy('industries.id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Published products whose category (if any) is also visible. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->where(fn ($q) => $q
            ->whereNull('product_category_id')
            ->orWhereHas('category', fn ($c) => $c->where('is_active', true)));
    }
}
