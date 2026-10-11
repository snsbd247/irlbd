<?php

namespace App\Http\Requests\Admin;


class OfficeRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'image' => $this->media(),
            'is_active' => 'boolean',
        ];
    }
}
