<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\MediaUrl;

class SettingsService
{
    /** Setting values keyed by name, with defaults for keys never saved. */
    public function values(bool $publicOnly = true): array
    {
        $stored = Setting::allCached();
        $values = [];

        foreach (config('cms.settings') as $key => [$group, $type, $label, $rules, $public]) {
            if ($publicOnly && ! $public) {
                continue;
            }
            $value = $stored[$key]['value'] ?? null;
            $values[$key] = $type === 'boolean' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
        }

        return $values;
    }

    /** Public settings plus resolved absolute URLs for image settings. */
    public function publicPayload(): array
    {
        $values = $this->values(true);

        foreach (config('cms.settings') as $key => [$group, $type]) {
            if (in_array($type, ['image', 'file'], true) && array_key_exists($key, $values)) {
                $values[$key.'_url'] = MediaUrl::url($values[$key]);
            }
        }

        $values['map_embed_url'] = $this->safeMapUrl($values['map_embed_url'] ?? null);

        return $values;
    }

    /** Only embed URLs pointing at an allow-listed map provider are ever returned. */
    public function safeMapUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);

        if (($parts['scheme'] ?? '') !== 'https' || ! in_array($parts['host'] ?? '', config('cms.map_hosts'), true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if (str_contains($parts['host'], 'google')) {
            // Either the official "Embed a map" URL or the keyless ?output=embed form.
            $keyless = $path === '/maps' && str_contains($parts['query'] ?? '', 'output=embed');
            if (! str_starts_with($path, '/maps/embed') && ! $keyless) {
                return null;
            }
        }
        if (str_contains($parts['host'], 'openstreetmap') && ! str_starts_with($path, '/export/embed')) {
            return null;
        }

        return $url;
    }

    public function save(array $data): void
    {
        $definitions = config('cms.settings');

        foreach ($data as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }
            [$group, $type, , , $public] = $definitions[$key];

            if ($type === 'boolean') {
                $value = $value ? '1' : '0';
            }

            Setting::updateOrCreate(['key' => $key], [
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'is_public' => $public,
            ]);
        }
    }
}
