<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional allow-list for the admin API (ADMIN_ALLOWED_IPS, comma separated, CIDR
 * allowed, e.g. "103.12.34.56, 10.0.0.0/24"). Empty = no restriction.
 */
class RestrictAdminIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('cms.admin_allowed_ips', []);

        if ($allowed && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            return response()->json(['message' => 'The admin panel is not available from your network.'], 403);
        }

        return $next($request);
    }
}
