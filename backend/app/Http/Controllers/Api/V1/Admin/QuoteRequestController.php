<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\QuoteStatusRequest;
use App\Http\Resources\QuoteRequestResource;
use App\Models\QuoteRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteRequestController extends CrudController
{
    protected string $model = QuoteRequest::class;

    protected string $resource = QuoteRequestResource::class;

    protected string $request = QuoteStatusRequest::class;

    protected array $searchable = ['name', 'company', 'email', 'phone', 'product_name'];

    protected array $filters = ['status' => 'status'];

    protected array $with = ['product'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'name', 'status'];

    /** Opening a request marks it as read (status stays "new" until someone sends a quote). */
    public function show($id)
    {
        $quote = $this->find($id);
        $this->authorize('view', $quote);
        $quote->read_at ??= now();
        $quote->save();

        return new QuoteRequestResource($quote->load('product'));
    }

    public function store()
    {
        abort(405, 'Quotation requests are created from the public website only.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', QuoteRequest::class);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Date', 'Product', 'Quantity', 'Specification', 'Needed by', 'Name', 'Company', 'Email', 'Phone', 'Message', 'Status']);
            QuoteRequest::orderByDesc('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map([EnquiryController::class, 'csvSafe'], [
                        $r->id, $r->created_at, $r->product_name, $r->quantity, $r->specification, $r->needed_by?->toDateString(),
                        $r->name, $r->company, $r->email, $r->phone, $r->message, $r->status,
                    ]));
                }
            });
            fclose($out);
        }, 'quote-requests-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
