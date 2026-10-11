<?php

namespace App\Http\Requests\Admin;

class TestimonialRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'designation' => 'nullable|string|max:120',
            'company' => 'nullable|string|max:150',
            'quote' => 'required|string|max:1500',
            'photo' => $this->media(),
            'rating' => 'nullable|integer|min:1|max:5',
            'is_active' => 'boolean',
        ];
    }
}
