<?php

namespace App\Services;

use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends a campaign in small batches, each triggered by an HTTP request from the admin
 * panel, so no cron job or queue worker is needed on shared hosting. A delivery row per
 * subscriber makes it resumable and guarantees nobody receives the same campaign twice.
 */
class NewsletterSender
{
    public function batchSize(): int
    {
        return max(1, min(100, (int) config('cms.newsletter_batch_size', 20)));
    }

    public function remaining(NewsletterCampaign $campaign): int
    {
        return NewsletterSubscriber::where('status', 'subscribed')
            ->whereNotIn('id', NewsletterDelivery::where('newsletter_campaign_id', $campaign->id)->select('newsletter_subscriber_id'))
            ->count();
    }

    /** @return array{sent: int, failed: int, remaining: int, done: bool} */
    public function sendBatch(NewsletterCampaign $campaign): array
    {
        // One batch at a time per campaign, even if the button is double-clicked or two tabs are open.
        $lock = Cache::lock('newsletter-batch-'.$campaign->id, 120);
        if (! $lock->get()) {
            return ['sent' => 0, 'failed' => 0, 'remaining' => $this->remaining($campaign), 'done' => false, 'busy' => true];
        }

        try {
            if ($campaign->status === 'draft') {
                $campaign->update([
                    'status' => 'sending',
                    'started_at' => now(),
                    'recipients_total' => NewsletterSubscriber::where('status', 'subscribed')->count(),
                ]);
            }

            $subscribers = NewsletterSubscriber::where('status', 'subscribed')
                ->whereNotIn('id', NewsletterDelivery::where('newsletter_campaign_id', $campaign->id)->select('newsletter_subscriber_id'))
                ->orderBy('id')
                ->limit($this->batchSize())
                ->get();

            $sent = $failed = 0;
            foreach ($subscribers as $subscriber) {
                try {
                    Mail::to($subscriber->email)->send(new NewsletterMail($campaign, $subscriber));
                    $status = 'sent';
                    $error = null;
                    $sent++;
                } catch (\Throwable $e) {
                    $status = 'failed';
                    $error = Str::limit($e->getMessage(), 250, '');
                    $failed++;
                }
                NewsletterDelivery::create([
                    'newsletter_campaign_id' => $campaign->id,
                    'newsletter_subscriber_id' => $subscriber->id,
                    'status' => $status,
                    'error' => $error,
                ]);
            }

            $remaining = $this->remaining($campaign);
            $campaign->increment('sent_count', $sent);
            $campaign->increment('failed_count', $failed);
            if ($remaining === 0) {
                $campaign->update(['status' => 'sent', 'finished_at' => now()]);
            }

            return ['sent' => $sent, 'failed' => $failed, 'remaining' => $remaining, 'done' => $remaining === 0];
        } finally {
            $lock->release();
        }
    }
}
