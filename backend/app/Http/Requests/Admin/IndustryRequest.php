<?php

namespace App\Http\Requests\Admin;

class IndustryRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'slug' => $this->slug('industries'),
            'icon' => $this->icon(),
            'is_active' => 'boolean',
        ];
    }
}
