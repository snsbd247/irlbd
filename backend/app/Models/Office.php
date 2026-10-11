<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use Sortable;

    protected $fillable = ['name', 'address', 'phone', 'email', 'image', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
