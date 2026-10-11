<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ContactSubmissionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'read_at' => $this->date($this->read_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
