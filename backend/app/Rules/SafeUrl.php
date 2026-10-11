<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts site-relative paths ("/products", "#top"), http(s) URLs, mailto: and tel: links.
 * Rejects javascript:, data: and any other scheme.
 */
class SafeUrl implements ValidationRule
{
    public function __construct(protected bool $httpsOnly = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || strlen($value) > 500 || preg_match('/[\s<>"\'`]/', $value)) {
            $fail('The :attribute must be a valid link.');

            return;
        }

        if ($this->httpsOnly) {
            if (! preg_match('#^https://[^/]+#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
                $fail('The :attribute must be a valid https:// URL.');
            }

            return;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return;
        }
        if (str_starts_with($value, '#')) {
            return;
        }
        if (preg_match('#^(mailto|tel):[^\s]+$#i', $value)) {
            return;
        }
        if (preg_match('#^https?://#i', $value) && filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return;
        }

        $fail('The :attribute must start with "/", "#", "http(s)://", "mailto:" or "tel:".');
    }
}
