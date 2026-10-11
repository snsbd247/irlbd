<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function url(?string $path): ?string
    {
        return MediaUrl::url($path);
    }

    protected function isAdmin(Request $request): bool
    {
        return $request->is('api/*/admin/*') || $request->is('api/*/admin');
    }

    protected function date($value): ?string
    {
        return $value?->toIso8601String();
    }
}
