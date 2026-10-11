<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Rules\MediaPath;
use App\Services\SettingsService;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $settings) {}

    public function show(): JsonResponse
    {
        Gate::authorize('manage-settings');

        $values = $this->settings->values(false);
        $urls = [];
        foreach (config('cms.settings') as $key => [$group, $type]) {
            if ($type === 'image') {
                $urls[$key] = MediaUrl::url($values[$key] ?? null);
            }
        }

        return response()->json(['values' => $values, 'image_urls' => $urls]);
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('manage-settings');

        $rules = [];
        foreach (config('cms.settings') as $key => [$group, $type, $label, $rule]) {
            $rules[$key] = $type === 'image' ? ['nullable', 'string', 'max:255', new MediaPath] : 'sometimes|'.$rule;
        }

        $data = $request->validate($rules);

        if (! empty($data['map_embed_url']) && ! $this->settings->safeMapUrl($data['map_embed_url'])) {
            return response()->json([
                'message' => 'The map URL must be a Google Maps embed URL (https://www.google.com/maps/embed?...) or an OpenStreetMap embed URL.',
                'errors' => ['map_embed_url' => ['Unsupported map provider or URL format.']],
            ], 422);
        }

        $this->settings->save($data);

        return $this->show();
    }
}
