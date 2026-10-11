<?php

namespace App\Http\Requests\Admin;

use App\Models\QuoteRequest;

class QuoteStatusRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:'.implode(',', QuoteRequest::STATUSES),
            'admin_note' => 'nullable|string|max:5000',
        ];
    }
}
