<?php

namespace App\Http\Requests\Admin;


class MilestoneRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'year' => 'required|string|max:20',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'image' => $this->media(),
            'is_active' => 'boolean',
        ];
    }
}
