<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\DealerRequest;
use App\Http\Resources\DealerResource;
use App\Models\Dealer;

class DealerController extends CrudController
{
    protected string $model = Dealer::class;

    protected string $resource = DealerResource::class;

    protected string $request = DealerRequest::class;

    protected array $searchable = ['name', 'district', 'address', 'contact_person', 'phone'];

    protected array $filters = ['is_active' => 'is_active', 'district' => 'district', 'type' => 'type'];
}
