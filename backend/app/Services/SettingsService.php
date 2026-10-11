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

        // Translated text for the current (non-default) language, falling back to the default.
        $locale = app()->getLocale();
        if ($locale !== config('cms.default_locale')) {
            foreach ($this->translations()[$locale] ?? [] as $key => $text) {
                if (filled($text) && array_key_exists($key, $values)) {
                    $values[$key] = $text;
                }
            }
        }
        $values['locale'] = $locale;

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

    /**
     * Setting translations: ['bn' => ['footer_about' => '...']]. Stored in the translations
     * table (type "Setting") and deliberately kept out of the settings cache.
     */
    public function translations(): array
    {
        $ids = Setting::query()->pluck('key', 'id');
        $out = [];
        \App\Models\Translation::where('translatable_type', 'Setting')->get()
            ->each(function ($t) use ($ids, &$out) {
                if (isset($ids[$t->translatable_id])) {
                    $out[$t->locale][$ids[$t->translatable_id]] = $t->value;
                }
            });

        return $out;
    }

    public function saveTranslations(?array $byLocale): void
    {
        $allowed = config('cms.translatable_settings');
        $locales = array_diff(array_keys(config('cms.locales')), [config('cms.default_locale')]);

        foreach ($byLocale ?? [] as $locale => $values) {
            if (! in_array($locale, $locales, true) || ! is_array($values)) {
                continue;
            }
            foreach ($values as $key => $value) {
                if (! in_array($key, $allowed, true) || ! isset(config('cms.settings')[$key])) {
                    continue;
                }
                $setting = Setting::firstOrCreate(['key' => $key], [
                    'group' => config('cms.settings')[$key][0], 'type' => config('cms.settings')[$key][1], 'is_public' => true,
                ]);
                $value = is_string($value) ? trim(strip_tags($value)) : null;
                $match = ['translatable_type' => 'Setting', 'translatable_id' => $setting->id, 'locale' => $locale, 'field' => 'value'];
                if ($value === null || $value === '') {
                    \App\Models\Translation::where($match)->delete();
                } else {
                    \App\Models\Translation::updateOrCreate($match, ['value' => mb_substr($value, 0, 1000)]);
                }
            }
        }
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
