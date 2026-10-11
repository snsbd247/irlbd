<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Sortable;

    protected $fillable = ['name', 'designation', 'photo', 'signature', 'message', 'quote', 'is_founder', 'sort_order', 'is_active'];

    protected $casts = ['is_founder' => 'boolean', 'is_active' => 'boolean', 'message' => SanitizedHtml::class];
}
