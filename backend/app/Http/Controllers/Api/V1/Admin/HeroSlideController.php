<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\HeroSlideRequest;
use App\Http\Resources\HeroSlideResource;
use App\Models\HeroSlide;

class HeroSlideController extends CrudController
{
    protected string $model = HeroSlide::class;

    protected string $resource = HeroSlideResource::class;

    protected string $request = HeroSlideRequest::class;

    protected array $searchable = ['title', 'eyebrow'];

    protected array $filters = ['is_active' => 'is_active'];
}
