<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'status', 'token', 'ip_address', 'subscribed_at', 'unsubscribed_at'];

    protected $hidden = ['token'];

    protected $casts = ['subscribed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            $subscriber->token ??= Str::random(48);
        });
    }
}
