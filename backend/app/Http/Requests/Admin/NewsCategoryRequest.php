<?php

namespace App\Http\Requests\Admin;


class NewsCategoryRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => $this->slug('news_categories'),
            'is_active' => 'boolean',
        ];
    }
}
