<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JobApplication extends Model
{
    public const STATUSES = ['new', 'shortlisted', 'interview', 'rejected', 'hired'];

    protected $fillable = [
        'job_post_id', 'job_title', 'name', 'email', 'phone', 'cover_letter', 'cv_path', 'cv_original_name',
        'cv_mime', 'cv_size', 'status', 'admin_note', 'ip_address', 'user_agent', 'read_at',
    ];

    protected $hidden = ['cv_path'];

    protected $casts = ['read_at' => 'datetime', 'cv_size' => 'integer'];

    protected static function booted(): void
    {
        // The CV is personal data: remove the file together with the application.
        static::deleted(fn (self $application) => Storage::disk('local')->delete($application->cv_path));
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }
}
