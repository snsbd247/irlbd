<?php

namespace App\Http\Requests\Admin;

use App\Rules\MediaPath;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorization is enforced by policies in the controllers; requests only validate.
 */
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** ID of the record being updated (first route parameter), or null when creating. */
    protected function routeId(): ?int
    {
        $params = $this->route()?->parameters() ?? [];
        $first = reset($params);

        return $first === false ? null : (int) (is_object($first) ? $first->getKey() : $first);
    }

    protected function isUpdate(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH'], true);
    }

    protected function media(): array
    {
        return ['nullable', 'string', 'max:255', new MediaPath];
    }

    protected function link(): array
    {
        return ['nullable', 'string', 'max:500', new SafeUrl];
    }

    protected function icon(): string
    {
        return 'nullable|string|in:'.implode(',', config('cms.icons'));
    }

    protected function slug(string $table): array
    {
        return ['nullable', 'string', 'max:190', 'alpha_dash', 'unique:'.$table.',slug'.($this->routeId() ? ','.$this->routeId() : '')];
    }

    protected function seo(): array
    {
        return [
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }
}
