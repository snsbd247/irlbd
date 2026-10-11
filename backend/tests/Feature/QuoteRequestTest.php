<?php

namespace Tests\Feature;

use App\Mail\QuoteConfirmation;
use App\Mail\QuoteRequested;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    protected function valid(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Karim Rahman',
            'company' => 'Fresh Foods Ltd',
            'email' => 'Karim@Example.com',
            'phone' => '+880 1711-000000',
            'quantity' => '50,000 pcs',
            'specification' => '500 ml clear PET, 28 mm neck',
            'needed_by' => now()->addMonth()->toDateString(),
            'message' => 'Please quote <b>FOB</b> Dhaka.',
        ];
    }

    public function test_quote_request_is_validated(): void
    {
        $this->postJson('/api/v1/quote', ['email' => 'bad', 'phone' => 'x', 'needed_by' => '2000-01-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'quantity', 'needed_by']);
    }

    public function test_quote_request_is_stored_with_product_snapshot(): void
    {
        $product = Product::create(['name' => 'PET Water Bottle', 'status' => 'published']);

        $this->postJson('/api/v1/quote', $this->valid(['product_id' => $product->id]))->assertCreated();

        $quote = QuoteRequest::sole();
        $this->assertSame('PET Water Bottle', $quote->product_name);
        $this->assertSame('karim@example.com', $quote->email);
        $this->assertSame('Please quote FOB Dhaka.', $quote->message);
        $this->assertSame('new', $quote->status);
    }

    public function test_draft_products_cannot_be_referenced(): void
    {
        $draft = Product::create(['name' => 'Secret', 'status' => 'draft']);

        $this->postJson('/api/v1/quote', $this->valid(['product_id' => $draft->id]))->assertCreated();

        $this->assertNull(QuoteRequest::sole()->product_id);
    }

    public function test_honeypot_discards_bots(): void
    {
        $this->postJson('/api/v1/quote', $this->valid(['website' => 'spam']))->assertCreated();
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_notification_email_is_sent_when_configured(): void
    {
        Mail::fake();
        app(SettingsService::class)->save(['site_name' => 'IR', 'enquiry_notify_email' => 'sales@example.com']);

        $this->postJson('/api/v1/quote', $this->valid())->assertCreated();

        Mail::assertSent(QuoteRequested::class, fn ($mail) => $mail->hasTo('sales@example.com') && $mail->hasReplyTo('karim@example.com'));
    }

    public function test_notification_email_neutralises_injected_markup(): void
    {
        $this->postJson('/api/v1/quote', $this->valid([
            'name' => 'Eve [click here](https://evil.example)',
            'quantity' => '10 | injected',
            'message' => '![x](https://evil.example/pixel.png) <script>alert(1)</script>',
        ]))->assertCreated();

        $html = (new QuoteRequested(QuoteRequest::sole()))->render();

        $this->assertStringNotContainsString('href="https://evil.example"', $html);
        $this->assertStringNotContainsString('<img src="https://evil.example', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('click here', $html);
    }

    public function test_admin_can_manage_quotes_and_public_cannot_read_them(): void
    {
        $this->postJson('/api/v1/quote', $this->valid())->assertCreated();
        $id = QuoteRequest::sole()->id;

        $this->getJson('/api/v1/admin/quote-requests')->assertUnauthorized();
        $this->actingAs($this->editor())->spa()->getJson('/api/v1/admin/quote-requests')->assertForbidden();

        $support = $this->support();
        $this->actingAs($support)->spa()->getJson('/api/v1/admin/quote-requests')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($support)->spa()->getJson("/api/v1/admin/quote-requests/{$id}")->assertOk()->assertJsonPath('quantity', '50,000 pcs');
        $this->assertNotNull(QuoteRequest::find($id)->read_at);

        $this->actingAs($support)->spa()->putJson("/api/v1/admin/quote-requests/{$id}", ['status' => 'quoted', 'admin_note' => 'BDT 4.50/pc'])
            ->assertOk()->assertJsonPath('status', 'quoted');
        $this->actingAs($support)->spa()->putJson("/api/v1/admin/quote-requests/{$id}", ['status' => 'bogus'])->assertStatus(422);
        $this->actingAs($support)->spa()->postJson('/api/v1/admin/quote-requests', ['name' => 'x'])->assertStatus(405);

        $csv = $this->actingAs($support)->spa()->get('/api/v1/admin/quote-requests/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Fresh Foods Ltd', $csv);

        $this->actingAs($support)->spa()->getJson('/api/v1/admin/dashboard')->assertJsonPath('stats.quotes.total', 1);
    }

    public function test_quote_gets_reference_and_customer_confirmation(): void
    {
        Mail::fake();

        $ref = $this->postJson('/api/v1/quote', $this->valid())->assertCreated()->json('reference');

        $this->assertMatchesRegularExpression('/^IRQ-\d{4}-\d{5}$/', $ref);
        $this->assertSame($ref, QuoteRequest::sole()->reference);
        Mail::assertSent(QuoteConfirmation::class, fn ($mail) => $mail->hasTo('karim@example.com'));

        $html = (new QuoteConfirmation(QuoteRequest::sole()))->render();
        $this->assertStringContainsString($ref, $html);
        $this->assertStringContainsString('track-quote?ref='.$ref, $html);
    }

    public function test_tracking_needs_matching_reference_and_email(): void
    {
        $ref = $this->postJson('/api/v1/quote', $this->valid())->json('reference');
        $quote = QuoteRequest::sole();
        $quote->update(['status' => 'quoted', 'customer_note' => 'Quotation emailed today.', 'admin_note' => 'internal only']);

        $this->postJson('/api/v1/quote/track', ['reference' => strtolower($ref), 'email' => 'KARIM@example.com '])
            ->assertOk()
            ->assertJsonPath('status', 'quoted')
            ->assertJsonPath('customer_note', 'Quotation emailed today.')
            ->assertJsonMissingPath('admin_note')
            ->assertJsonMissingPath('phone');
        $this->assertNotNull($quote->fresh()->status_changed_at);

        $wrongEmail = $this->postJson('/api/v1/quote/track', ['reference' => $ref, 'email' => 'other@example.com'])->assertNotFound()->json('message');
        $noSuchRef = $this->postJson('/api/v1/quote/track', ['reference' => 'IRQ-2026-99999', 'email' => 'karim@example.com'])->assertNotFound()->json('message');
        $this->assertSame($wrongEmail, $noSuchRef);

        $this->postJson('/api/v1/quote/track', ['reference' => '1 OR 1=1', 'email' => 'karim@example.com'])->assertUnprocessable();
    }
}

