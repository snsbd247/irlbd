<?php

namespace App\Http\Requests\Admin;


class CertificationRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'logo' => $this->media(),
            'description' => 'nullable|string|max:500',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
