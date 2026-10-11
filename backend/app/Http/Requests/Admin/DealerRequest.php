<?php

namespace App\Http\Requests\Admin;

use App\Models\Dealer;
use Illuminate\Validation\Rule;

class DealerRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'type' => ['required', Rule::in(Dealer::TYPES)],
            'district' => ['required', Rule::in(Dealer::DISTRICTS)],
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'map_url' => 'nullable|url:https|max:500',
            'is_active' => 'boolean',
        ];
    }
}
