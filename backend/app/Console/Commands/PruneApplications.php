<?php

namespace App\Console\Commands;

use App\Models\JobApplication;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Deletes job applications (and their private CV files) older than the retention
 * period from Settings → Contact (default 365 days, 0 = keep forever).
 * Run daily from cron: php artisan cms:prune-applications
 */
class PruneApplications extends Command
{
    protected $signature = 'cms:prune-applications {--days= : Override the retention period} {--dry-run : Only count}';

    protected $description = 'Delete job applications and CVs older than the retention period';

    public function handle(): int
    {
        $setting = Setting::get('cv_retention_days');
        $days = (int) ($this->option('days') ?? (($setting === null || $setting === '') ? 365 : $setting));

        if ($days <= 0) {
            $this->info('CV retention is set to "keep forever"; nothing deleted.');

            return self::SUCCESS;
        }

        $query = JobApplication::where('created_at', '<', now()->subDays($days));

        if ($this->option('dry-run')) {
            $this->info($query->count()." application(s) older than {$days} days would be deleted.");

            return self::SUCCESS;
        }

        $deleted = 0;
        // Model events delete each CV file from private storage.
        $query->chunkById(100, function ($rows) use (&$deleted) {
            foreach ($rows as $row) {
                $row->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} application(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
