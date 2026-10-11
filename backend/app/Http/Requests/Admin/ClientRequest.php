<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeUrl;

class ClientRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'logo' => $this->media(),
            'website_url' => ['nullable', 'string', 'max:255', new SafeUrl(httpsOnly: false)],
            'is_active' => 'boolean',
        ];
    }
}
