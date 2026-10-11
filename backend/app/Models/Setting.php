<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const CACHE_KEY = 'settings.all';

    protected $fillable = ['key', 'value', 'group', 'type', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** @return array<string, array{value: mixed, type: string, group: string, is_public: bool}> */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->get()
            ->mapWithKeys(fn ($s) => [$s->key => [
                'value' => $s->value,
                'type' => $s->type,
                'group' => $s->group,
                'is_public' => $s->is_public,
            ]])->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key]['value'] ?? $default;
    }
}
