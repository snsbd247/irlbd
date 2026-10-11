<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\FaqResource;
use App\Http\Resources\OfficeResource;
use App\Models\ContactSubmission;
use App\Models\Faq;
use App\Models\Office;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function info(): JsonResponse
    {
        return response()->json([
            'offices' => OfficeResource::collection(Office::active()->ordered()->get()),
            'faqs' => FaqResource::collection(Faq::active()->ordered()->get()),
            'subjects' => ContactRequest::SUBJECTS,
        ]);
    }

    public function store(ContactRequest $request): JsonResponse
    {
        // Honeypot: bots fill the hidden "website" field. Pretend success, store nothing.
        if ($request->filled('website')) {
            return response()->json(['message' => 'Thank you! Your message has been sent.'], 201);
        }

        $submission = ContactSubmission::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]);

        $this->notify($submission);

        return response()->json(['message' => 'Thank you! Your message has been sent. Our team will contact you shortly.'], 201);
    }

    /** Best-effort email notification; enquiry is already stored, so mail failures never break the form. */
    protected function notify(ContactSubmission $submission): void
    {
        $to = Setting::get('enquiry_notify_email');
        if (! $to) {
            return;
        }

        try {
            Mail::raw(
                "New website enquiry\n\nName: {$submission->name}\nEmail: {$submission->email}\nPhone: {$submission->phone}\n".
                "Subject: {$submission->subject}\n\n{$submission->message}",
                fn ($m) => $m->to($to)->replyTo($submission->email)->subject('Website enquiry: '.$submission->subject)
            );
        } catch (\Throwable $e) {
            Log::warning('Enquiry notification failed: '.$e->getMessage());
        }
    }
}
