<?php

namespace App\Http\Requests\Admin;


class ProductCategoryRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'slug' => $this->slug('product_categories'),
            'icon' => $this->icon(),
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:50000',
            'image' => $this->media(),
            'is_active' => 'boolean',
            ...$this->seo(),
        ];
    }
}
