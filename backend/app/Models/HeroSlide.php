<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use Sortable;

    protected $fillable = [
        'eyebrow', 'title', 'highlight', 'title_suffix', 'subtitle', 'image',
        'primary_label', 'primary_url', 'secondary_label', 'secondary_url', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
