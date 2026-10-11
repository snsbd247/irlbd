<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

class Html
{
    /**
     * Clean admin-authored rich text against the strict "cms" allow-list.
     * Scripts, iframes, event handlers, styles and javascript: URLs are removed.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return Purifier::clean($html, 'cms');
    }

    public static function plain(?string $html, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)).'…' : $text;
    }
}
