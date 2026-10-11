<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequest extends Model
{
    public const STATUSES = ['new', 'quoted', 'ordered', 'closed'];

    protected $fillable = [
        'product_id', 'product_name', 'name', 'company', 'email', 'phone', 'quantity', 'specification',
        'needed_by', 'message', 'status', 'admin_note', 'ip_address', 'user_agent', 'read_at',
    ];

    protected $casts = ['needed_by' => 'date', 'read_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
