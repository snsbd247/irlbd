<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * One-time installer for shared hosts WITHOUT SSH/terminal access.
 *
 * Disabled (404) unless INSTALL_TOKEN (>= 32 chars) is set in .env.
 * First run: migrations + seeders, then a lock file is written so seeding never runs again.
 * Later runs (e.g. after uploading a new release): migrations only.
 * Remove INSTALL_TOKEN from .env as soon as you are done.
 *
 *   curl -X POST https://api.example.com/api/install -d "token=YOUR_TOKEN"
 */
class InstallController extends Controller
{
    protected const LOCK = 'installed.lock';

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('cms.install_token');

        if (strlen($token) < 32) {
            abort(404);
        }

        if (! hash_equals($token, (string) $request->input('token'))) {
            abort(403, 'Invalid installer token.');
        }

        @set_time_limit(300);

        $steps = [];
        Artisan::call('migrate', ['--force' => true]);
        $steps['migrate'] = trim(Artisan::output());

        if (! Storage::disk('local')->exists(self::LOCK)) {
            Artisan::call('db:seed', ['--force' => true]);
            $steps['seed'] = trim(Artisan::output());
            Storage::disk('local')->put(self::LOCK, now()->toIso8601String());
        }

        Artisan::call('optimize:clear');

        return response()->json([
            'message' => 'Done. Remove INSTALL_TOKEN and ADMIN_PASSWORD from .env now.',
            'steps' => $steps,
        ]);
    }
}
