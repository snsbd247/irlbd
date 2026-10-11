<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Permission;
use App\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Static definitions the admin UI needs to render its forms (single source of truth).
 */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = [];
        foreach (config('cms.settings') as $key => [$group, $type, $label, $rules, $public]) {
            $settings[] = compact('key', 'group', 'type', 'label', 'public');
        }

        return response()->json([
            'section_types' => config('sections'),
            'icons' => config('cms.icons'),
            'settings' => $settings,
            'permissions' => Permission::orderBy('id')->get(['slug', 'label', 'group']),
            'page_templates' => Page::TEMPLATES,
            'menu_locations' => MenuItem::LOCATIONS,
            'upload_folders' => config('cms.uploads.folders'),
            'upload_max_kb' => config('cms.uploads.max_kb'),
            'contact_subjects' => ContactRequest::SUBJECTS,
            'frontend_url' => config('cms.frontend_url'),
            'locales' => config('cms.locales'),
            'default_locale' => config('cms.default_locale'),
            'translatable_settings' => config('cms.translatable_settings'),
        ]);
    }

    public function regenerateSitemap(SitemapService $sitemap): JsonResponse
    {
        Gate::authorize('manage-settings');
        $path = $sitemap->write();

        return response()->json([
            'written' => (bool) $path,
            'message' => $path
                ? 'sitemap.xml regenerated.'
                : 'SITEMAP_OUTPUT_PATH is not set or not writable. The live sitemap is still available from the API at /api/v1/sitemap.xml.',
        ]);
    }
}
