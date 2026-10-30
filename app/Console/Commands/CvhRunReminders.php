<?php

namespace App\Console\Commands;

use App\Http\Controllers\Web\CronJobsController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CVH Africa — runs the template's reminder/renewal jobs (Web\CronJobsController) from the server's cron, once an hour:
 *
 *   0 * * * *  /opt/cpanel/ea-php83/root/usr/bin/php /home/fspnafri/lms.fspnafrica.org/artisan cvh:reminders >/dev/null 2>&1
 *
 * Each reminder runs every N hours, N being its "hours before" value in Admin → Settings → General → Reminders,
 * because those jobs notify everything due within the next N hours and keep no record of what they sent
 * (running them more often would repeat the same reminder). Daily jobs run at midnight (app timezone).
 */
class CvhRunReminders extends Command
{
    protected $signature = 'cvh:reminders {--all : run every job now, ignoring the schedule}';

    protected $description = 'Send live-class, meeting and subscription reminders and renew subscriptions (run hourly from cron)';

    /** job => reminder setting holding its interval in hours, or 'daily' / 'hourly' */
    private const JOBS = [
        'sendSessionsReminder' => 'webinar_reminder_schedule',
        'sendMeetingsReminder' => 'meeting_reminder_schedule',
        'sendSubscribeReminder' => 'subscribe_reminder_schedule',
        'reminderBeforeExpirationSubscribes' => 'notification_before_subscription_plan_expires',
        'renewSubscriptions' => 'daily',
        // Not scheduled: installments, gifts, events, abandoned carts (not used by CVH; the template's demo
        // installment orders would otherwise trigger "overdue" messages).
    ];

    public function handle(): int
    {
        $hour = (int)now()->format('G');
        $controller = app(CronJobsController::class);

        foreach (self::JOBS as $job => $schedule) {
            $every = match ($schedule) {
                'daily' => 24,
                default => max(1, min(24, (int)(getRemindersSettings($schedule) ?: 1))),
            };

            if (!$this->option('all') && $hour % $every !== 0) {
                continue;
            }

            try {
                $result = $controller->{$job}(request());
                $message = method_exists($result, 'getContent') ? $result->getContent() : 'done';
                $this->line("{$job}: {$message}");
            } catch (\Throwable $exception) {
                // one failing job (e.g. a feature that is not set up) must not stop the others
                Log::warning("cvh:reminders {$job} failed: " . $exception->getMessage());
                $this->warn("{$job}: failed ({$exception->getMessage()})");
            }
        }

        // Google Meet attendance for the live class metrics (Google keeps it only 30 days, so fetch it every hour)
        try {
            $this->call('cvh:meet-metrics');
        } catch (\Throwable $exception) {
            Log::warning('cvh:reminders meet metrics failed: ' . $exception->getMessage());
            $this->warn("meet metrics: failed ({$exception->getMessage()})");
        }

        return self::SUCCESS;
    }
}
