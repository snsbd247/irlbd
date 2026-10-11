<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notifies the sales team about a new contact-form enquiry. */
class EnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactSubmission $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Website enquiry: '.$this->enquiry->subject,
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.enquiry-received', with: [
            'adminUrl' => rtrim(config('cms.frontend_url'), '/').'/admin/enquiries',
        ]);
    }
}
