<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;

class ServiceController extends CrudController
{
    protected string $model = Service::class;

    protected string $resource = ServiceResource::class;

    protected string $request = ServiceRequest::class;

    protected array $searchable = ['title', 'short_description'];

    protected array $filters = ['is_active' => 'is_active'];
}
