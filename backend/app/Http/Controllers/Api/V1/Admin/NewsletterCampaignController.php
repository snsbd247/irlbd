<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\NewsletterCampaignRequest;
use App\Http\Resources\NewsletterCampaignResource;
use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\ActivityLogger;
use App\Services\NewsletterSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterCampaignController extends CrudController
{
    protected string $model = NewsletterCampaign::class;

    protected string $resource = NewsletterCampaignResource::class;

    protected string $request = NewsletterCampaignRequest::class;

    protected array $searchable = ['subject'];

    protected array $filters = ['status' => 'status'];

    protected array $with = ['author'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'subject'];

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        // Content cannot change once sending has started (every recipient gets the same email).
        abort_if($model && $model->status !== 'draft', 422, 'This campaign has already been sent and can no longer be edited.');
        if (! $model) {
            $data['created_by'] = $request->user()->id;
        }

        return $data;
    }

    protected function beforeDelete(Model $model): void
    {
        abort_if($model->status === 'sending', 422, 'Finish or wait for sending before deleting this campaign.');
    }

    /** The email exactly as subscribers will see it; the admin renders it in a sandboxed iframe (srcdoc). */
    public function preview($id): JsonResponse
    {
        $campaign = $this->find($id);
        $this->authorize('view', $campaign);

        return response()->json(['html' => (new NewsletterMail($campaign))->render()]);
    }

    public function test(Request $request, $id): JsonResponse
    {
        $campaign = $this->find($id);
        $this->authorize('view', $campaign);
        $data = $request->validate(['email' => 'nullable|email|max:150']);
        $to = $data['email'] ?? $request->user()->email;

        Mail::to($to)->send(new NewsletterMail($campaign));

        return response()->json(['message' => "Test email sent to {$to}."]);
    }

    public function sendBatch($id, NewsletterSender $sender, ActivityLogger $logger): JsonResponse
    {
        $campaign = $this->find($id);
        $this->authorize('update', $campaign);
        abort_if($campaign->status === 'sent', 422, 'This campaign has already been sent.');

        if ($campaign->status === 'draft') {
            abort_if(NewsletterSubscriber::where('status', 'subscribed')->doesntExist(), 422, 'There are no active subscribers.');
            $logger->log('newsletter_sending', $campaign);
        }

        $result = $sender->sendBatch($campaign);

        return response()->json($result + ['campaign' => new NewsletterCampaignResource($campaign->fresh('author'))]);
    }

    public function recipients(NewsletterSender $sender): JsonResponse
    {
        $this->authorize('viewAny', NewsletterCampaign::class);

        return response()->json([
            'subscribed' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            'batch_size' => $sender->batchSize(),
        ]);
    }
}
