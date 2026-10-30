<?php
/**
 * CVH Africa — tables for Google Meet engagement metrics (Admin → Education → Live Class Metrics).
 * Filled by `php artisan cvh:meet-metrics` (also run every hour by cvh:reminders) from Google's Meet API:
 *   cvh_meet_conferences  one row per time a session's Meet was actually held (actual start/end, recording, transcript)
 *   cvh_meet_attendance   one row per person in that meeting (first join, last leave, time in the call, joins)
 * After deploying, reconnect the Google account once (Settings → General → Features → Google Meet) so it also
 * allows reading meeting attendance, and enable the "Google Meet REST API" in the Google Cloud project.
 * Run from the project root:  php database/cvh/26_meet_metrics.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_meet_conferences')) {
    Schema::create('cvh_meet_conferences', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('session_id')->index();
        $table->string('conference_name')->unique(); // conferenceRecords/{id}
        $table->unsignedInteger('started_at')->nullable();
        $table->unsignedInteger('ended_at')->nullable(); // null while the meeting is still going
        $table->unsignedInteger('participants')->default(0);
        $table->string('recording_url', 512)->nullable();
        $table->string('transcript_url', 512)->nullable();
        $table->unsignedInteger('synced_at');
    });
    echo "Created table cvh_meet_conferences.\n";
}

if (!Schema::hasTable('cvh_meet_attendance')) {
    Schema::create('cvh_meet_attendance', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('conference_id')->index();
        $table->unsignedInteger('session_id')->index();
        $table->string('participant_name')->unique(); // conferenceRecords/{id}/participants/{id}
        $table->string('display_name')->nullable();
        $table->string('kind', 16); // signed_in, anonymous, phone
        $table->unsignedInteger('user_id')->nullable()->index(); // the LMS user, matched by name among the course's learners and instructors
        $table->unsignedInteger('first_join')->nullable();
        $table->unsignedInteger('last_leave')->nullable();
        $table->unsignedInteger('seconds')->default(0); // total time in the call, all joins together
        $table->unsignedSmallInteger('joins')->default(1);
    });
    echo "Created table cvh_meet_attendance.\n";
}

echo "Meet metrics tables ready.\n";
