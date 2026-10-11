<?php

namespace App\Models\Concerns;

use App\Casts\SanitizedHtml;
use App\Models\Translation;
use App\Support\Html;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Field translations stored in the `translations` table.
 *
 * - The default language (English) lives in the model's own columns.
 * - When the app locale is another language (set by the SetLocale middleware for public
 *   API requests), translations for that locale are eager-loaded by a global scope and
 *   returned transparently by getAttribute(); empty translations fall back to English.
 * - Admin requests always run in the default locale and edit translations explicitly.
 *
 * Models declare: protected array $translatable = ['title', ...];
 */
trait HasTranslations
{
    public static function bootHasTranslations(): void
    {
        static::addGlobalScope('translations', function (Builder $query) {
            $locale = app()->getLocale();
            if ($locale !== config('cms.default_locale')) {
                $query->with(['translations' => fn ($q) => $q->where('locale', $locale)]);
            }
        });

        static::deleted(fn ($model) => $model->translations()->delete());
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function translatableFields(): array
    {
        return $this->translatable ?? [];
    }

    public function getAttribute($key)
    {
        $locale = app()->getLocale();

        if ($locale !== config('cms.default_locale')
            && in_array($key, $this->translatable ?? [], true)
            && $this->relationLoaded('translations')) {
            $translation = $this->getRelation('translations')
                ->first(fn ($t) => $t->field === $key && $t->locale === $locale);

            if ($translation && filled($translation->value)) {
                return $translation->value;
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Save translations: ['bn' => ['title' => '...', ...]]. Unknown locales/fields are
     * ignored, rich-text fields are sanitized, empty values delete the translation.
     */
    public function syncTranslations(?array $byLocale): void
    {
        if (! is_array($byLocale)) {
            return;
        }

        $locales = array_diff(array_keys(config('cms.locales')), [config('cms.default_locale')]);
        $casts = $this->getCasts();

        foreach ($byLocale as $locale => $fields) {
            if (! in_array($locale, $locales, true) || ! is_array($fields)) {
                continue;
            }
            foreach ($fields as $field => $value) {
                if (! in_array($field, $this->translatableFields(), true)) {
                    continue;
                }
                $value = is_string($value) ? trim($value) : null;
                if (($casts[$field] ?? null) === SanitizedHtml::class) {
                    $value = Html::clean($value);
                }

                if ($value === null || $value === '') {
                    $this->translations()->where('locale', $locale)->where('field', $field)->delete();
                } else {
                    $this->translations()->updateOrCreate(['locale' => $locale, 'field' => $field], ['value' => $value]);
                }
            }
        }

        $this->unsetRelation('translations');
    }

    /** For the admin: ['bn' => ['title' => '...']] */
    public function translationsArray(): array
    {
        $rows = $this->relationLoaded('translations') ? $this->getRelation('translations') : $this->translations()->get();
        $out = [];
        foreach ($rows as $row) {
            $out[$row->locale][$row->field] = $row->value;
        }

        return $out;
    }
}
