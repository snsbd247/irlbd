<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    public const FIELDS = ['name', 'capacity', 'weight', 'neck_size', 'color', 'moq'];

    protected $fillable = ['product_id', 'name', 'capacity', 'weight', 'neck_size', 'color', 'moq', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
