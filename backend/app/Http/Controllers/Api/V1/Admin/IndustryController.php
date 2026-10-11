<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\IndustryRequest;
use App\Http\Resources\IndustryResource;
use App\Models\Industry;

class IndustryController extends CrudController
{
    protected string $model = Industry::class;

    protected string $resource = IndustryResource::class;

    protected string $request = IndustryRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['is_active' => 'is_active'];

    protected array $withCount = ['products'];
}
