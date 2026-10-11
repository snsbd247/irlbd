<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\EnquiryRequest;
use App\Http\Resources\ContactSubmissionResource;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends CrudController
{
    protected string $model = ContactSubmission::class;

    protected string $resource = ContactSubmissionResource::class;

    protected string $request = EnquiryRequest::class;

    protected array $searchable = ['name', 'email', 'subject', 'phone'];

    protected array $filters = ['status' => 'status'];

    protected array $order = ['created_at', 'desc'];

    protected array $sortableColumns = ['id', 'created_at', 'name', 'status'];

    /** Opening an enquiry marks it as read. */
    public function show($id)
    {
        $enquiry = $this->find($id);
        $this->authorize('view', $enquiry);

        if ($enquiry->status === 'new') {
            $enquiry->update(['status' => 'read', 'read_at' => now()]);
        }

        return new ContactSubmissionResource($enquiry);
    }

    public function store()
    {
        abort(405, 'Enquiries are created from the public contact form only.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ContactSubmission::class);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Status']);
            ContactSubmission::orderByDesc('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map([self::class, 'csvSafe'], [
                        $r->id, $r->created_at, $r->name, $r->email, $r->phone, $r->subject, $r->message, $r->status,
                    ]));
                }
            });
            fclose($out);
        }, 'enquiries-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Prevent spreadsheet formula injection from user-submitted values. */
    public static function csvSafe($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
