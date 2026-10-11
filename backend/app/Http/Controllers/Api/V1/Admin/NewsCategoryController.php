<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\NewsCategoryRequest;
use App\Http\Resources\NewsCategoryResource;
use App\Models\NewsCategory;

class NewsCategoryController extends CrudController
{
    protected string $model = NewsCategory::class;

    protected string $resource = NewsCategoryResource::class;

    protected string $request = NewsCategoryRequest::class;

    protected array $searchable = ['name'];

    protected array $withCount = ['posts'];
}
