<?php

namespace App\Http\Requests\Admin;

class NewsletterCampaignRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:200',
            'preheader' => 'nullable|string|max:200',
            'content' => 'required|string|max:200000',
        ];
    }
}
