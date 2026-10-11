<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\FaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;

class FaqController extends CrudController
{
    protected string $model = Faq::class;

    protected string $resource = FaqResource::class;

    protected string $request = FaqRequest::class;

    protected array $searchable = ['question', 'answer'];

    protected array $filters = ['is_active' => 'is_active'];
}
