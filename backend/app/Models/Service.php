<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasSlug, Sortable;

    protected $fillable = ['title', 'slug', 'icon', 'short_description', 'description', 'image', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'description' => SanitizedHtml::class];
}
