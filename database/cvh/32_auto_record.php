<?php
/**
 * CVH Africa — turn on Google Meet's automatic recording for every live session that hasn't ended yet (new and
 * edited sessions get it by themselves), and show what Google answered for each. Recordings go to the connected
 * Google account's Drive → "Meet Recordings". Before this works: add the meetings.space.settings scope in Google
 * Cloud and reconnect Google in Admin → Settings → General → Features → Google Meet.
 *
 *   php database/cvh/32_auto_record.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Session;
use App\Support\CvhGoogleMeet;

if (!CvhGoogleMeet::connected()) {
    exit("Google Meet is not connected: Admin → Settings → General → Features → Google Meet → Connect Google account\n");
}
echo "Connected Google account (owns and records every class): " . CvhGoogleMeet::settings()['account_email'] . "\n\n";

$sessions = Session::query()->where('session_api', 'google_meet')->whereRaw('(date + duration * 60) > ?', [time()])->orderBy('date')->get();
if ($sessions->isEmpty()) {
    exit("No upcoming Google Meet sessions.\n");
}

$ok = 0;
foreach ($sessions as $session) {
    $error = CvhGoogleMeet::autoRecord($session);
    $when = (new DateTime('@' . $session->date))->setTimezone(new DateTimeZone('Africa/Nairobi'))->format('j M Y H:i');
    printf("  %-6s session %d  %s  %s%s\n", $error ? 'FAILED' : 'on', $session->id, $when, mb_strimwidth((string)$session->title, 0, 40, '…'), $error ? "\n         Google said: {$error}" : '');
    $ok += $error ? 0 : 1;
}
echo "\nAutomatic recording on for {$ok} of {$sessions->count()} upcoming sessions.\n";
if ($ok < $sessions->count()) {
    echo "\"insufficient authentication scopes\" or \"permission\": add the meetings.space.settings scope in Google Cloud, then reconnect Google.\n";
}
