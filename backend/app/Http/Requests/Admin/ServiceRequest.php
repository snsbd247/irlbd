<?php

namespace App\Http\Requests\Admin;


class ServiceRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'slug' => $this->slug('services'),
            'icon' => $this->icon(),
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:100000',
            'image' => $this->media(),
            'is_active' => 'boolean',
        ];
    }
}
