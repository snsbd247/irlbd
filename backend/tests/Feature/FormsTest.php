<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class FormsTest extends TestCase
{
    protected function validContact(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Karim Rahman',
            'email' => 'Karim@Example.com',
            'phone' => '+880 1711-000000',
            'subject' => 'Product Quotation',
            'message' => 'Please send a quotation for 50,000 PET bottles.',
        ];
    }

    public function test_contact_form_is_validated(): void
    {
        $this->postJson('/api/v1/contact', ['email' => 'not-an-email', 'subject' => 'Hack', 'phone' => '<script>', 'message' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'phone', 'message']);

        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_contact_form_stores_enquiry(): void
    {
        $this->postJson('/api/v1/contact', $this->validContact(['message' => 'Hello <b>team</b>, please call me back soon.']))
            ->assertCreated();

        $enquiry = ContactSubmission::sole();
        $this->assertSame('karim@example.com', $enquiry->email);
        $this->assertSame('new', $enquiry->status);
        $this->assertSame('Hello team, please call me back soon.', $enquiry->message);
    }

    public function test_contact_honeypot_discards_bots(): void
    {
        $this->postJson('/api/v1/contact', $this->validContact(['website' => 'http://spam.example']))->assertCreated();

        $this->assertDatabaseCount('contact_submissions', 0);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        RateLimiter::clear('forms');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact', $this->validContact())->assertCreated();
        }

        $this->postJson('/api/v1/contact', $this->validContact())->assertStatus(429);
    }

    public function test_opening_an_enquiry_marks_it_read(): void
    {
        $enquiry = ContactSubmission::create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Other', 'message' => 'Hello there!']);

        $this->actingAs($this->support())->spa()->getJson('/api/v1/admin/enquiries/'.$enquiry->id)
            ->assertOk()->assertJsonPath('status', 'read');

        $this->actingAs($this->support())->spa()->putJson('/api/v1/admin/enquiries/'.$enquiry->id, ['status' => 'replied', 'admin_note' => 'Called back'])
            ->assertOk()->assertJsonPath('status', 'replied');
    }

    public function test_newsletter_subscription(): void
    {
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'News@Example.com'])->assertCreated();
        // Duplicate gives the same response (no enumeration) and no duplicate row.
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'news@example.com'])->assertCreated();

        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame('news@example.com', NewsletterSubscriber::first()->email);
    }

    public function test_newsletter_validation(): void
    {
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'nope'])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_newsletter_unsubscribe_with_token(): void
    {
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'bye@example.com']);
        $subscriber = NewsletterSubscriber::first();

        $this->postJson('/api/v1/newsletter/unsubscribe', ['token' => $subscriber->token])->assertOk();

        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
    }

    public function test_subscriber_tokens_are_not_exposed_to_admins_list(): void
    {
        $this->postJson('/api/v1/newsletter/subscribe', ['email' => 'list@example.com']);

        $this->actingAs($this->admin())->spa()->getJson('/api/v1/admin/subscribers')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.token');
    }

    public function test_csv_export_escapes_formulas(): void
    {
        ContactSubmission::create(['name' => '=HYPERLINK("x")', 'email' => 'a@example.com', 'subject' => 'Other', 'message' => 'Hello there!']);

        $csv = $this->actingAs($this->support())->spa()->get('/api/v1/admin/enquiries/export')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
