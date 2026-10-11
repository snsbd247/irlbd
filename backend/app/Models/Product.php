<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasSlug, Publishable, Sortable;

    protected string $slugFrom = 'name';

    protected $fillable = [
        'product_category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'image',
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
