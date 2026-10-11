<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use App\Rules\SafeUrl;

class MenuItemRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'location' => 'required|in:'.implode(',', \App\Models\MenuItem::LOCATIONS),
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('menu_items', 'id')->whereNull('parent_id')->where('location', $this->input('location')),
                Rule::notIn(array_filter([$this->routeId()])),
            ],
            'label' => 'required|string|max:80',
            'link_type' => 'required|in:page,url',
            'page_id' => 'nullable|required_if:link_type,page|integer|exists:pages,id',
            'url' => ['nullable', 'required_if:link_type,url', 'string', 'max:500', new SafeUrl],
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
