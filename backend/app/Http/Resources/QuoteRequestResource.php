<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class QuoteRequestResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_slug' => $this->product?->slug,
            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'quantity' => $this->quantity,
            'specification' => $this->specification,
            'needed_by' => $this->needed_by?->toDateString(),
            'message' => $this->message,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'customer_note' => $this->customer_note,
            'status_changed_at' => $this->date($this->status_changed_at),
            'ip_address' => $this->ip_address,
            'read_at' => $this->date($this->read_at),
            'created_at' => $this->date($this->created_at),
        ];
    }
}
