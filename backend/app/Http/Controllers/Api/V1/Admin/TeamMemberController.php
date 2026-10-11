<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\TeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;

class TeamMemberController extends CrudController
{
    protected string $model = TeamMember::class;

    protected string $resource = TeamMemberResource::class;

    protected string $request = TeamMemberRequest::class;

    protected array $searchable = ['name', 'designation'];

    protected array $filters = ['is_active' => 'is_active', 'is_founder' => 'is_founder'];
}
