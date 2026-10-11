<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public API language: ?lang=bn (falls back to the default language).
 * Admin routes never use this middleware, so editors always work on the default
 * language and manage translations explicitly.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $lang = (string) $request->query('lang', '');
        $locale = array_key_exists($lang, config('cms.locales')) ? $lang : config('cms.default_locale');
        app()->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
