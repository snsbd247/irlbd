<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Publishable
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public static function bootPublishable(): void
    {
        static::saving(function ($model) {
            if ($model->status === self::STATUS_PUBLISHED && in_array('published_at', $model->getFillable(), true) && ! $model->published_at) {
                $model->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        $query->where($this->getTable().'.status', self::STATUS_PUBLISHED);

        if (in_array('published_at', $this->getFillable(), true)) {
            $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
        }

        return $query;
    }
}
