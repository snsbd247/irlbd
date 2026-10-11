<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;

class ClientController extends CrudController
{
    protected string $model = Client::class;

    protected string $resource = ClientResource::class;

    protected string $request = ClientRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['is_active' => 'is_active'];
}
