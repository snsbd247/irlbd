<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Logs create/update/delete of CMS content, but only when done by a signed-in admin
 * (seeders, installers and public form submissions are not admin actions).
 */
class AdminActivityObserver
{
    public function __construct(protected ActivityLogger $logger) {}

    protected function shouldLog(): bool
    {
        return Auth::check();
    }

    public function created(Model $model): void
    {
        if ($this->shouldLog()) {
            $this->logger->log('created', $model);
        }
    }

    public function updated(Model $model): void
    {
        if (! $this->shouldLog()) {
            return;
        }
        $diff = $this->logger->diff($model);
        // Ignore bookkeeping-only updates; login / 2FA changes have their own explicit log entries.
        $meaningful = array_diff(array_keys($diff), [
            'read_at', 'last_login_at', 'sort_order', 'remember_token',
            'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        ]);
        if ($meaningful) {
            $this->logger->log('updated', $model, $diff);
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldLog()) {
            $this->logger->log('deleted', $model);
        }
    }
}
