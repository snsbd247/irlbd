<?php

namespace App\Http\Requests\Admin;


class RoleRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,slug',
        ];
    }
}
