<?php

namespace App\Http\Requests\Admin;


class TeamMemberRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'designation' => 'nullable|string|max:150',
            'photo' => $this->media(),
            'signature' => $this->media(),
            'message' => 'nullable|string|max:100000',
            'quote' => 'nullable|string|max:500',
            'is_founder' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
