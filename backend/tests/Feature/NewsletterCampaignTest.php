<?php

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterCampaignTest extends TestCase
{
    protected function subscribers(int $active, int $unsubscribed = 0): void
    {
        for ($i = 1; $i <= $active; $i++) {
            NewsletterSubscriber::create(['email' => "user{$i}@example.com", 'status' => 'subscribed', 'subscribed_at' => now()]);
        }
        for ($i = 1; $i <= $unsubscribed; $i++) {
            NewsletterSubscriber::create(['email' => "gone{$i}@example.com", 'status' => 'unsubscribed']);
        }
    }

    protected function campaign(): int
    {
        return $this->actingAs($this->admin())->spa()->postJson('/api/v1/admin/newsletter-campaigns', [
            'subject' => 'October update', 'preheader' => 'New products',
            'content' => '<p>Hello!</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>',
        ])->assertCreated()->json('id');
    }

    public function test_content_is_sanitized_and_preview_includes_unsubscribe(): void
    {
        $id = $this->campaign();
        $this->assertStringNotContainsString('<script', NewsletterCampaign::find($id)->content);
        $this->assertStringNotContainsString('javascript:', NewsletterCampaign::find($id)->content);

        $html = $this->actingAs($this->admin())->spa()->getJson("/api/v1/admin/newsletter-campaigns/{$id}/preview")->assertOk()->json('html');
        $this->assertStringContainsString('Hello!', $html);
        $this->assertStringContainsString('Unsubscribe', $html);
    }

    public function test_batched_sending_is_resumable_and_never_duplicates(): void
    {
        Mail::fake();
        config(['cms.newsletter_batch_size' => 2]);
        $this->subscribers(5, 2);
        $id = $this->campaign();
        $admin = $this->admin();

        $first = $this->actingAs($admin)->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertOk();
        $first->assertJsonPath('sent', 2)->assertJsonPath('remaining', 3)->assertJsonPath('done', false)
            ->assertJsonPath('campaign.status', 'sending')->assertJsonPath('campaign.recipients_total', 5);

        // Editing is blocked once sending has started.
        $this->actingAs($admin)->spa()->putJson("/api/v1/admin/newsletter-campaigns/{$id}", ['subject' => 'Changed', 'content' => '<p>x</p>'])->assertStatus(422);

        $this->actingAs($admin)->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertJsonPath('remaining', 1);
        $this->actingAs($admin)->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")
            ->assertJsonPath('done', true)->assertJsonPath('campaign.status', 'sent')->assertJsonPath('campaign.sent_count', 5);

        $this->actingAs($admin)->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertStatus(422);

        Mail::assertSentCount(5);
        Mail::assertNotSent(NewsletterMail::class, fn ($m) => $m->hasTo('gone1@example.com'));
        $this->assertSame(5, NewsletterDelivery::count());
        $this->assertSame(5, NewsletterDelivery::distinct('newsletter_subscriber_id')->count('newsletter_subscriber_id'));
    }

    public function test_each_email_has_a_personal_unsubscribe_link(): void
    {
        Mail::fake();
        $this->subscribers(1);
        $id = $this->campaign();
        $token = NewsletterSubscriber::first()->token;

        $this->actingAs($this->admin())->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertOk();

        Mail::assertSent(NewsletterMail::class, function (NewsletterMail $mail) use ($token) {
            return str_contains($mail->render(), 'newsletter/unsubscribe?token='.$token)
                && str_contains($mail->headers()->text['List-Unsubscribe'], $token);
        });

        // The link works and that person is skipped by future campaigns.
        $this->postJson('/api/v1/newsletter/unsubscribe', ['token' => $token])->assertOk();
        $this->assertSame('unsubscribed', NewsletterSubscriber::first()->status);
    }

    public function test_test_email_and_permissions(): void
    {
        Mail::fake();
        $id = $this->campaign();

        $this->actingAs($this->editor())->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertForbidden();
        $this->actingAs($this->support())->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/test", ['email' => 'me@example.com'])->assertOk();
        Mail::assertSent(NewsletterMail::class, fn ($m) => $m->hasTo('me@example.com'));

        // No subscribers: sending is refused instead of marking the campaign as sent.
        $this->actingAs($this->support())->spa()->postJson("/api/v1/admin/newsletter-campaigns/{$id}/send-batch")->assertStatus(422);
        $this->assertSame('draft', NewsletterCampaign::find($id)->status);
    }
}
