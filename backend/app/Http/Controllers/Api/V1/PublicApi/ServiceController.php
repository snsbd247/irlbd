<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::active()->ordered()->get());
    }

    public function show(string $slug): ServiceResource
    {
        return new ServiceResource(Service::active()->where('slug', $slug)->firstOrFail());
    }
}
