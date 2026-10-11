<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\JobPostRequest;
use App\Http\Resources\JobPostResource;
use App\Models\JobPost;

class JobPostController extends CrudController
{
    protected string $model = JobPost::class;

    protected string $resource = JobPostResource::class;

    protected string $request = JobPostRequest::class;

    protected array $searchable = ['title', 'department', 'location'];

    protected array $filters = ['status' => 'status'];

    protected array $withCount = ['applications'];
}
