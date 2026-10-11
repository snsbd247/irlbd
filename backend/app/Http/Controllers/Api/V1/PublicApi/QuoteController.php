<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequestForm;
use App\Mail\QuoteConfirmation;
use App\Mail\QuoteRequested;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function store(QuoteRequestForm $request): JsonResponse
    {
        $message = 'Thank you! Your quotation request has been received. Our sales team will contact you shortly.';

        // Honeypot: pretend success, store nothing.
        if ($request->filled('website')) {
            return response()->json(['message' => $message], 201);
        }

        $data = $request->safe()->except('website');
        // Only published products can be referenced from the public form.
        $product = isset($data['product_id']) ? Product::visible()->find($data['product_id']) : null;

        $quote = QuoteRequest::create([
            ...$data,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]);

        $this->notify($quote);
        $this->confirmToCustomer($quote);

        return response()->json([
            'message' => $message,
            'reference' => $quote->reference,
        ], 201);
    }

    /**
     * Public status lookup. Needs both the reference and the email used on the form,
     * so references alone can't be enumerated. Same answer for "wrong email" and
     * "no such reference".
     */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:20', 'regex:/^IRQ-\d{4}-\d{5,}$/i'],
            'email' => 'required|email:rfc|max:150',
        ]);

        $quote = QuoteRequest::where('reference', strtoupper(trim($data['reference'])))->first();
        if (! $quote || ! hash_equals(strtolower($quote->email), strtolower(trim($data['email'])))) {
            return response()->json(['message' => 'We could not find a quotation request with this reference and email address.'], 404);
        }

        return response()->json([
            'reference' => $quote->reference,
            'status' => $quote->status,
            'status_label' => QuoteRequest::STATUS_LABELS[$quote->status] ?? $quote->status,
            'customer_note' => $quote->customer_note,
            'product' => $quote->product_name ?: 'Custom / not listed',
            'quantity' => $quote->quantity,
            'submitted_at' => $quote->created_at?->toIso8601String(),
            'updated_at' => ($quote->status_changed_at ?? $quote->created_at)?->toIso8601String(),
        ]);
    }

    protected function notify(QuoteRequest $quote): void
    {
        $to = Setting::get('enquiry_notify_email');
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new QuoteRequested($quote));
        } catch (\Throwable $e) {
            Log::warning('Quote notification failed: '.$e->getMessage());
        }
    }

    /** Confirmation to the customer with the tracking reference. */
    protected function confirmToCustomer(QuoteRequest $quote): void
    {
        try {
            Mail::to($quote->email, $quote->name)->send(new QuoteConfirmation($quote));
        } catch (\Throwable $e) {
            Log::warning('Quote confirmation failed: '.$e->getMessage());
        }
    }
}
