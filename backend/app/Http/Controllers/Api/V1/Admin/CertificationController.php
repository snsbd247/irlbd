<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\CertificationRequest;
use App\Http\Resources\CertificationResource;
use App\Models\Certification;

class CertificationController extends CrudController
{
    protected string $model = Certification::class;

    protected string $resource = CertificationResource::class;

    protected string $request = CertificationRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['is_active' => 'is_active', 'is_verified' => 'is_verified'];
}
