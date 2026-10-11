<?php

namespace App\Http\Requests\Admin;


class ProductRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'product_category_id' => 'nullable|integer|exists:product_categories,id',
            'name' => 'required|string|max:200',
            'slug' => $this->slug('products'),
            'sku' => 'nullable|string|max:100',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:200000',
            'image' => $this->media(),
            'datasheet' => $this->media(),
            'is_featured' => 'boolean',
            'status' => 'required|in:draft,published',
            ...$this->seo(),
            'images' => 'nullable|array|max:20',
            'images.*.path' => ['required', ...$this->media()],
            'images.*.alt' => 'nullable|string|max:255',
            'specifications' => 'nullable|array|max:50',
            'specifications.*.label' => 'required|string|max:120',
            'specifications.*.value' => 'required|string|max:255',
            'variants' => 'nullable|array|max:50',
            'variants.*.name' => 'required|string|max:120',
            'variants.*.capacity' => 'nullable|string|max:60',
            'variants.*.weight' => 'nullable|string|max:60',
            'variants.*.neck_size' => 'nullable|string|max:60',
            'variants.*.color' => 'nullable|string|max:80',
            'variants.*.moq' => 'nullable|string|max:60',
            'industry_ids' => 'nullable|array|max:30',
            'industry_ids.*' => 'integer|exists:industries,id',
        ];
    }
}
