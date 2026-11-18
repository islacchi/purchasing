<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:prune
                            {--days=365 : Delete entries older than this many days}
                            {--dry-run : Show how many rows would be deleted without deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune activity log entries older than a retention window (default: 365 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->error('--days must be 1 or greater. Refusing to prune.');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($days);

        $query = ActivityLog::query()->where('created_at', '<', $cutoff);

        if ($dryRun) {
            $count = $query->count();
            $this->info("Dry run: {$count} " . ($count === 1 ? 'entry' : 'entries')
                . " older than {$cutoff->toDateString()} would be deleted (--days={$days}).");

            return self::SUCCESS;
        }

        // No confirmation prompt by design: this runs unattended from the
        // scheduler; --dry-run is the preview mechanism.
        $deleted = $query->delete();

        $this->info("Pruned {$deleted} " . ($deleted === 1 ? 'entry' : 'entries')
            . " older than {$cutoff->toDateString()} (--days={$days}).");

        return self::SUCCESS;
    }
}
