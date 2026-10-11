<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name'];

    protected $fillable = ['name', 'logo', 'website_url', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
