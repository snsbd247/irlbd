<?php

namespace App\Rules;

use App\Models\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Image fields may only reference files that exist in the media library,
 * so admins can never inject arbitrary or script URLs into image sources.
 */
class MediaPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! Media::where('path', $value)->exists()) {
            $fail('The selected image does not exist in the media library.');
        }
    }
}
