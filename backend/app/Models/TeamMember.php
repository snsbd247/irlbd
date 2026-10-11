<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['name', 'designation', 'message', 'quote'];

    protected $fillable = ['name', 'designation', 'photo', 'signature', 'message', 'quote', 'is_founder', 'sort_order', 'is_active'];

    protected $casts = ['is_founder' => 'boolean', 'is_active' => 'boolean', 'message' => SanitizedHtml::class];
}
