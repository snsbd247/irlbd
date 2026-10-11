<?php

namespace App\Support;

/**
 * Escapes user-supplied text placed inside Markdown emails, so visitors cannot inject
 * links, images or table syntax into notifications sent to staff.
 */
class MarkdownText
{
    public static function escape(?string $text): string
    {
        $text = (string) $text;
        // "<" and ">" are left to Blade's HTML escaping.
        $text = preg_replace('/([\\\\`*_{}\[\]()#+\-.!|~])/', '\\\\$1', $text);

        return $text;
    }

    /** Single-line variant for table cells (newlines would break the table row). */
    public static function cell(?string $text): string
    {
        return static::escape(preg_replace('/\s+/', ' ', (string) $text));
    }
}
