<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;

class TestimonialController extends CrudController
{
    protected string $model = Testimonial::class;

    protected string $resource = TestimonialResource::class;

    protected string $request = TestimonialRequest::class;

    protected array $searchable = ['name', 'company', 'quote'];

    protected array $filters = ['is_active' => 'is_active'];
}
