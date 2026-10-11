<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use Sortable;

    protected $fillable = ['name', 'designation', 'company', 'quote', 'photo', 'rating', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'rating' => 'integer'];
}
