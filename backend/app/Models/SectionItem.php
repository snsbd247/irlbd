<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['title', 'subtitle', 'description', 'value'];

    protected $fillable = [
        'page_section_id', 'title', 'subtitle', 'description', 'value', 'icon', 'image', 'link_url', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }
}
