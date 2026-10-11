<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\OfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;

class OfficeController extends CrudController
{
    protected string $model = Office::class;

    protected string $resource = OfficeResource::class;

    protected string $request = OfficeRequest::class;

    protected array $searchable = ['name', 'address'];

    protected array $filters = ['is_active' => 'is_active'];
}
