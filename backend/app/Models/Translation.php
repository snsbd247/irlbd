<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    // Only ever filled by trusted code (HasTranslations / SettingsService), never from raw input.
    protected $fillable = ['translatable_type', 'translatable_id', 'locale', 'field', 'value'];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Removes translations of rows deleted in bulk or by a database cascade
     * (those deletes do not fire model events).
     *
     * @param  iterable<int>  $ids
     */
    public static function forget(string $type, iterable $ids): void
    {
        $ids = collect($ids)->filter()->values();
        if ($ids->isNotEmpty()) {
            static::where('translatable_type', $type)->whereIn('translatable_id', $ids)->delete();
        }
    }
}
