<?php

namespace App\Http\Requests\Admin;

use App\Models\JobApplication;

class JobApplicationStatusRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:'.implode(',', JobApplication::STATUSES),
            'admin_note' => 'nullable|string|max:5000',
        ];
    }
}
