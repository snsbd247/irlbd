<?php

namespace App\Http\Requests\Admin;


class SubscriberRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:subscribed,unsubscribed',
        ];
    }
}
