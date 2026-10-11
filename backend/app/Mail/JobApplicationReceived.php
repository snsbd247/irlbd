<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells HR a new application arrived. The CV is NOT attached; it stays in the protected admin panel. */
class JobApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public JobApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New job application: '.($this->application->job_title ?: 'General'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.job-application-received', with: [
            'adminUrl' => rtrim(config('cms.frontend_url'), '/').'/admin/job-applications',
        ]);
    }
}
