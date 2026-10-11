<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'status', 'admin_note', 'ip_address', 'user_agent', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
