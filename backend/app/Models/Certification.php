<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'logo', 'description', 'is_verified', 'sort_order', 'is_active'];

    protected $casts = ['is_verified' => 'boolean', 'is_active' => 'boolean'];
}
