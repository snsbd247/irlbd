<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'designation', 'company', 'quote'];

    protected $fillable = ['name', 'designation', 'company', 'quote', 'photo', 'rating', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'rating' => 'integer'];
}
