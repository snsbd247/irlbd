<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'color'];

    public const FIELDS = ['name', 'capacity', 'weight', 'neck_size', 'color', 'moq'];

    protected $fillable = ['product_id', 'name', 'capacity', 'weight', 'neck_size', 'color', 'moq', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
