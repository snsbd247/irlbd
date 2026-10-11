<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Generates a unique slug from the model's slug source when none is supplied.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->{$model->slugSource()};
            $slug = Str::slug($model->slug ?: $source) ?: Str::lower(Str::random(8));
            $model->slug = static::uniqueSlug($slug, $model->getKey());
        });
    }

    protected function slugSource(): string
    {
        return property_exists($this, 'slugFrom') ? $this->slugFrom : 'title';
    }

    protected static function uniqueSlug(string $slug, $ignoreId = null): string
    {
        $base = Str::limit($slug, 180, '');
        $candidate = $base;
        $i = 2;
        while (static::query()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }
}
