<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasSlug, Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'short_description', 'description'];

    protected string $slugFrom = 'name';

    protected $fillable = [
        'name', 'slug', 'icon', 'short_description', 'description', 'image', 'sort_order', 'is_active', 'meta_title', 'meta_description',
    ];

    protected $casts = ['is_active' => 'boolean', 'description' => SanitizedHtml::class];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
