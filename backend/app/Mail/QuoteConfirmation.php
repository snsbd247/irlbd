<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the customer: "we received your request", with the tracking reference. */
class QuoteConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $quote) {}

    public function envelope(): Envelope
    {
        $replyTo = Setting::get('enquiry_notify_email') ?: Setting::get('contact_email');

        return new Envelope(
            subject: 'We received your quotation request ('.$this->quote->reference.')',
            replyTo: $replyTo ? [new Address($replyTo)] : [],
        );
    }

    public function content(): Content
    {
        $base = rtrim(config('cms.frontend_url'), '/');

        return new Content(markdown: 'mail.quote-confirmation', with: [
            'trackUrl' => $base.'/track-quote?ref='.urlencode($this->quote->reference),
            'siteName' => Setting::get('site_name', config('app.name')),
        ]);
    }
}
