<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequest extends Model
{
    public const STATUSES = ['new', 'quoted', 'ordered', 'closed'];

    /** Customer-facing wording for the tracking page. */
    public const STATUS_LABELS = [
        'new' => 'Received — our sales team is reviewing your request',
        'quoted' => 'Quotation sent',
        'ordered' => 'Order confirmed',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'reference', 'customer_note', 'status_changed_at', 'product_id', 'product_name', 'name', 'company', 'email', 'phone', 'quantity', 'specification',
        'needed_by', 'message', 'status', 'admin_note', 'ip_address', 'user_agent', 'read_at',
    ];

    protected $casts = ['needed_by' => 'date', 'read_at' => 'datetime', 'status_changed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::created(function (QuoteRequest $quote) {
            if (! $quote->reference) {
                $quote->reference = sprintf('IRQ-%s-%05d', $quote->created_at?->format('Y') ?? date('Y'), $quote->id);
                $quote->saveQuietly();
            }
        });
        static::updating(function (QuoteRequest $quote) {
            if ($quote->isDirty('status')) {
                $quote->status_changed_at = now();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
