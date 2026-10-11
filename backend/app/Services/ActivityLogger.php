<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Audit trail of admin actions. Secrets are never stored, and long rich-text fields are
 * recorded only as "changed" to keep the log small and free of personal message content.
 */
class ActivityLogger
{
    protected const SECRET = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'token'];

    protected const LONG = ['content', 'description', 'message', 'cover_letter', 'admin_note', 'answer', 'quote', 'settings'];

    /** Keep the table small on shared hosting: occasionally prune entries older than the retention period. */
    protected const RETENTION_DAYS = 365;

    public function log(string $action, ?Model $subject = null, ?array $changes = null, ?User $user = null, ?string $label = null): void
    {
        $user ??= Auth::user();
        $request = request();

        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => Str::limit($label ?? ($subject ? $this->label($subject) : null) ?? '', 250, '') ?: null,
            'changes' => $changes ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 250, ''),
        ]);

        if (random_int(1, 200) === 1) {
            ActivityLog::where('created_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();
        }
    }

    public function label(Model $model): ?string
    {
        foreach (['name', 'title', 'label', 'question', 'email', 'key'] as $attr) {
            if (filled($model->getAttribute($attr))) {
                return (string) $model->getAttribute($attr);
            }
        }

        return '#'.$model->getKey();
    }

    /** Field-level diff for an update, with secrets removed and long values summarised. */
    public function diff(Model $model): array
    {
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, ['updated_at', 'created_at'], true)) {
                continue;
            }
            if (in_array($field, self::SECRET, true)) {
                $changes[$field] = ['changed' => true];

                continue;
            }
            if (in_array($field, self::LONG, true)) {
                $changes[$field] = ['changed' => true];

                continue;
            }
            $changes[$field] = [
                'from' => $this->short($model->getOriginal($field)),
                'to' => $this->short($new),
            ];
        }

        return $changes;
    }

    protected function short(mixed $value): mixed
    {
        if (is_bool($value) || is_null($value) || is_int($value)) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_array($value)) {
            return '[...]';
        }

        return Str::limit((string) $value, 120);
    }
}
