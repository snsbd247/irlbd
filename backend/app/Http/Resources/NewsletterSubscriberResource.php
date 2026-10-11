<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NewsletterSubscriberResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status,
            'subscribed_at' => $this->date($this->subscribed_at),
            'unsubscribed_at' => $this->date($this->unsubscribed_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
