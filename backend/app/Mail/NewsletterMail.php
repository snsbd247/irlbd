<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Support\MediaUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterCampaign $campaign, public ?NewsletterSubscriber $subscriber = null) {}

    public function unsubscribeUrl(): string
    {
        $token = $this->subscriber?->token ?? 'preview';

        return rtrim(config('cms.frontend_url'), '/').'/newsletter/unsubscribe?token='.$token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    /** One-click unsubscribe headers (RFC 8058), recognised by Gmail/Outlook. */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>',
        ]);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.newsletter', with: [
            'siteName' => Setting::get('site_name', config('app.name')),
            'logoUrl' => MediaUrl::url(Setting::get('logo_header')),
            'siteUrl' => rtrim(config('cms.frontend_url'), '/'),
            'address' => Setting::get('contact_address'),
            'unsubscribeUrl' => $this->unsubscribeUrl(),
        ]);
    }
}
