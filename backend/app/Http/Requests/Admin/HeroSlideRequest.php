<?php

namespace App\Http\Requests\Admin;


class HeroSlideRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'eyebrow' => 'nullable|string|max:150',
            'title' => 'required|string|max:150',
            'highlight' => 'nullable|string|max:150',
            'title_suffix' => 'nullable|string|max:150',
            'subtitle' => 'nullable|string|max:500',
            'image' => $this->media(),
            'primary_label' => 'nullable|string|max:60',
            'primary_url' => $this->link(),
            'secondary_label' => 'nullable|string|max:60',
            'secondary_url' => $this->link(),
            'is_active' => 'boolean',
        ];
    }
}
