<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Notifies the sales team about a new quotation request. */
class QuoteRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation request: '.($this->quote->product_name ?: $this->quote->name),
            replyTo: [new Address($this->quote->email, $this->quote->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quote-requested', with: [
            'adminUrl' => rtrim(config('cms.frontend_url'), '/').'/admin/quote-requests',
        ]);
    }
}
