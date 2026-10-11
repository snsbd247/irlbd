<?php

namespace App\Http\Requests\Admin;


class FaqRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:5000',
            'is_active' => 'boolean',
        ];
    }
}
