<?php

namespace App\Console\Commands;

use App\Models\Session;
use App\Support\CvhMeetMetrics as Metrics;
use Illuminate\Console\Command;

/**
 * CVH Africa — fetch Google Meet attendance for live classes (Admin → Education → Live Class Metrics).
 * Run every hour by cvh:reminders; by hand: `php artisan cvh:meet-metrics` or `--session=ID` for one session.
 */
class CvhMeetMetrics extends Command
{
    protected $signature = 'cvh:meet-metrics {--session= : sync only this session id} {--days=25 : how far back to look (Google keeps 30 days)}';

    protected $description = 'Fetch Google Meet attendance for recent live classes';

    public function handle(): int
    {
        if (!Metrics::ready()) {
            $this->warn('Google Meet is not connected, or database/cvh/26_meet_metrics.php has not been run.');
            return self::SUCCESS;
        }

        if ($this->option('session')) {
            $session = Session::query()->findOrFail((int)$this->option('session'));
            $this->line("Session {$session->id}: " . Metrics::syncSession($session) . ' meeting(s) found.');
            return self::SUCCESS;
        }

        $synced = Metrics::syncRecent((int)$this->option('days'));
        foreach ($synced as $sessionId => $meetings) {
            $this->line("Session {$sessionId}: {$meetings} meeting(s) found.");
        }
        $this->line(count($synced) . ' session(s) synced.');

        return self::SUCCESS;
    }
}
