<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\MilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;

class MilestoneController extends CrudController
{
    protected string $model = Milestone::class;

    protected string $resource = MilestoneResource::class;

    protected string $request = MilestoneRequest::class;

    protected array $searchable = ['year', 'title'];

    protected array $filters = ['is_active' => 'is_active'];
}
