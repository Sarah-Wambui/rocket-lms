<?php
/**
 * CVH Africa — check the live-class setup: is Google Meet connected and able to make meetings, what type an
 * instructor's latest courses really are, which live sessions they have (with Meet link), what Google said when a
 * session could not be made, and whether recordings have come in. Changes nothing (the Meet test event is deleted).
 *
 *   php database/cvh/check_live_sessions.php <instructor id or email>
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CvhGoogleMeet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$arg = $argv[1] ?? '';
$user = str_contains($arg, '@') ? App\User::where('email', $arg)->first() : App\User::find((int)$arg);
$time = fn($ts) => $ts ? (new DateTime('@' . $ts))->setTimezone(new DateTimeZone('Africa/Nairobi'))->format('j M Y H:i') : '-';
$types = ['webinar' => 'Live Class', 'course' => 'Course (videos/files)', 'text_lesson' => 'Text Lesson'];

echo "== Google Meet\n";
$apis = (array)(getFeaturesSettings('available_session_apis') ?? []);
echo "  offered in the session form: " . (in_array('google_meet', $apis) ? 'yes' : 'NO') . "   (all: " . implode(', ', $apis) . ")\n";
echo "  connected: " . (CvhGoogleMeet::connected() ? 'yes' : 'NO — Admin → Settings → General → Features → Google Meet → Connect Google account') . "\n";
if (CvhGoogleMeet::connected()) {
    try {
        echo "  test meeting: OK (" . CvhGoogleMeet::test() . ", deleted again)\n";
    } catch (Throwable $e) {
        echo "  test meeting: FAILED — " . $e->getMessage() . "\n";
    }
}

if ($user) {
    echo "\n== {$user->full_name} ({$user->role_name}, id {$user->id}) — latest courses\n";
    $courses = App\Models\Webinar::where(fn($q) => $q->where('creator_id', $user->id)->orWhere('teacher_id', $user->id))->orderByDesc('updated_at')->limit(3)->get();
    foreach ($courses as $course) {
        printf("  %d  %s  [%s, %s]\n", $course->id, $course->title, $types[$course->type] ?? $course->type, $course->status);
        $sessions = DB::table('sessions')->where('webinar_id', $course->id)->orderBy('date')->get();
        if ($sessions->isEmpty()) {
            echo "       no live sessions" . ($course->type !== 'webinar' ? " (only a Live Class can have them)" : '') . "\n";
        }
        foreach ($sessions as $s) {
            $title = DB::table('session_translations')->where('session_id', $s->id)->value('title');
            printf("       session %d  %-30s %-12s %s  %s  link: %s\n", $s->id, mb_strimwidth((string)$title, 0, 30, '…'), $s->session_api, $time($s->date), $s->status, $s->link ?: '-');
        }
    }
} elseif ($arg !== '') {
    echo "\nNo user {$arg}\n";
}

echo "\n== What Google said recently (storage/logs/laravel.log)\n";
$log = storage_path('logs/laravel.log');
$lines = is_file($log) ? preg_grep('/Google Meet|Google Calendar|meet-metrics|Meet API/i', explode("\n", (string)shell_exec('tail -n 4000 ' . escapeshellarg($log)))) : [];
foreach (array_slice($lines ?: ['  (nothing)'], -8) as $line) {
    echo '  ' . mb_strimwidth(trim($line), 0, 220, '…') . "\n";
}

echo "\n== Recordings collected from Meet\n";
if (Schema::hasTable('cvh_meet_conferences')) {
    $held = DB::table('cvh_meet_conferences')->count();
    $recorded = DB::table('cvh_meet_conferences')->whereNotNull('recording_url')->count();
    echo "  meetings held: {$held}, with a recording: {$recorded}\n";
    foreach (DB::table('cvh_meet_conferences')->orderByDesc('started_at')->limit(5)->get() as $c) {
        printf("  session %d  %s  recording: %s\n", $c->session_id, $time($c->started_at), $c->recording_url ?: '-');
    }
} else {
    echo "  table missing: run php database/cvh/26_meet_metrics.php\n";
}
