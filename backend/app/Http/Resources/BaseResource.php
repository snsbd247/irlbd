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

    /** Admin responses include all translations of translatable models. */
    public function resolve($request = null)
    {
        $data = parent::resolve($request);
        $request ??= request();

        if (is_array($data) && $this->resource instanceof \Illuminate\Database\Eloquent\Model
            && method_exists($this->resource, 'translationsArray') && $this->isAdmin($request)) {
            $data['translations'] = (object) $this->resource->translationsArray();
        }

        return $data;
    }

    protected function date($value): ?string
    {
        return $value?->toIso8601String();
    }
}
