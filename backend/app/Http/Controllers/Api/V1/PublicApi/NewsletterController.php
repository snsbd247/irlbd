<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email:rfc|max:150',
            'website' => 'nullable|max:0', // honeypot
        ]);

        $email = strtolower(trim($data['email']));
        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $email]);

        // Same response whether or not the address already exists (no email enumeration).
        if (! $subscriber->exists || $subscriber->status !== 'subscribed') {
            $subscriber->fill([
                'status' => 'subscribed',
                'ip_address' => $request->ip(),
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ])->save();
        }

        return response()->json(['message' => 'Thank you for subscribing to our newsletter!'], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|size:48']);

        $subscriber = NewsletterSubscriber::where('token', $data['token'])->first();
        $subscriber?->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);

        return response()->json(['message' => 'You have been unsubscribed.']);
    }
}
