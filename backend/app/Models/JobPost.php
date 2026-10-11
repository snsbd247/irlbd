<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Casts\SanitizedHtml;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    use HasSlug, Publishable, Sortable, HasTranslations;

    /** Fields that can be translated (see HasTranslations). */
    protected array $translatable = ['title', 'department', 'location', 'summary', 'description'];

    public const TYPES = ['full_time', 'part_time', 'contract', 'internship'];

    protected $fillable = [
        'title', 'slug', 'department', 'location', 'employment_type', 'vacancies', 'salary', 'deadline',
        'summary', 'description', 'status', 'published_at', 'sort_order', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'description' => SanitizedHtml::class,
        'deadline' => 'date',
        'published_at' => 'datetime',
        'vacancies' => 'integer',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Published and not past its deadline (the deadline day itself still counts). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->published()->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()));
    }

    public function isOpen(): bool
    {
        return $this->status === 'published' && (! $this->deadline || $this->deadline->endOfDay()->isFuture());
    }

    // Sortable's "active" scope is not meaningful for jobs; publication is used instead.
    public function scopeActive(Builder $query): Builder
    {
        return $query->open();
    }
}
