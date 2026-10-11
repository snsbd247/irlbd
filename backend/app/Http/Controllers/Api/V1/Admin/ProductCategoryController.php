<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;

class ProductCategoryController extends CrudController
{
    protected string $model = ProductCategory::class;

    protected string $resource = ProductCategoryResource::class;

    protected string $request = ProductCategoryRequest::class;

    protected array $searchable = ['name', 'short_description'];

    protected array $filters = ['is_active' => 'is_active'];

    protected array $withCount = ['products'];
}
