<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\SubscriberRequest;
use App\Http\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends CrudController
{
    protected string $model = NewsletterSubscriber::class;

    protected string $resource = NewsletterSubscriberResource::class;

    protected string $request = SubscriberRequest::class;

    protected array $searchable = ['email'];

    protected array $filters = ['status' => 'status'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'email'];

    public function store()
    {
        abort(405, 'Subscribers sign up from the public website.');
    }

    protected function prepare(array $data, FormRequest $request, ?Model $model): array
    {
        $data['unsubscribed_at'] = $data['status'] === 'unsubscribed' ? now() : null;

        return $data;
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Status', 'Subscribed at']);
            NewsletterSubscriber::orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map([EnquiryController::class, 'csvSafe'], [$r->email, $r->status, $r->subscribed_at]));
                }
            });
            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
