<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    protected $fillable = [
        'subject', 'preheader', 'content', 'status', 'recipients_total', 'sent_count', 'failed_count',
        'started_at', 'finished_at', 'created_by',
    ];

    protected $casts = [
        'content' => SanitizedHtml::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
