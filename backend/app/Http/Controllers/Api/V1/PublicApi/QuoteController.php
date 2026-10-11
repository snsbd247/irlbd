<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequestForm;
use App\Mail\QuoteRequested;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
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

        return response()->json(['message' => $message], 201);
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
}
