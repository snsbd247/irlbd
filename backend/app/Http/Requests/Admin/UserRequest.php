<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->routeId())],
            'password' => [$this->isUpdate() ? 'nullable' : 'required', 'string', Password::min(10)->letters()->numbers(), 'max:128'],
            'role_id' => 'required|integer|exists:roles,id',
            'is_active' => 'boolean',
        ];
    }
}
