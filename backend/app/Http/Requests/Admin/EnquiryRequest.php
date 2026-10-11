<?php

namespace App\Http\Requests\Admin;


class EnquiryRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:'.implode(',', \App\Models\ContactSubmission::STATUSES),
            'admin_note' => 'nullable|string|max:5000',
        ];
    }
}
