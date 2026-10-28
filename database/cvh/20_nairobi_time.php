<?php
/**
 * CVH Africa — run the site on Nairobi time (Africa/Nairobi, UTC+3).
 *
 *   php database/cvh/20_nairobi_time.php           show what would change
 *   php database/cvh/20_nairobi_time.php --apply   change it
 *
 * Sets Settings → General → Basic → Default time zone, and the time zone of users and courses that have none, UTC or
 * the template's demo default America/New_York (which made the admin see every class 7 hours early). Zones people
 * chose themselves (Kampala, Lagos, ...) are left alone. Times already saved are not moved: a session saved for
 * 18:45 Nairobi stays 18:45 Nairobi; this only changes how times are entered and shown.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

const ZONE = 'Africa/Nairobi';
const REPLACE = ['', 'UTC', 'America/New_York'];

$apply = in_array('--apply', $argv, true);
$toFix = fn($table) => DB::table($table)->where(fn($q) => $q->whereNull('timezone')->orWhereIn('timezone', REPLACE));

$generalId = DB::table('settings')->where('name', 'general')->value('id');
$rows = $generalId ? DB::table('setting_translations')->where('setting_id', $generalId)->get() : collect();
$current = $rows->map(fn($r) => (json_decode($r->value, true)['default_time_zone'] ?? '(not set)'))->unique()->implode(', ');

$users = $toFix('users')->count();
$courses = $toFix('webinars')->count();
$admins = $toFix('users')->whereIn('role_name', ['admin', 'teacher'])->pluck('full_name')->implode(', ');
$kept = DB::table('users')->whereNotNull('timezone')->whereNotIn('timezone', array_merge(REPLACE, [ZONE]))
    ->select('timezone', DB::raw('count(*) as n'))->groupBy('timezone')->pluck('n', 'timezone');

echo "Site default time zone now: {$current}\n";
echo "Users to switch to Nairobi (none / UTC / New York): {$users}" . ($admins ? " (admins and instructors among them: {$admins})" : '') . "\n";
echo "Courses to switch to Nairobi: {$courses}\n";
echo "Users who chose another zone, left alone: " . ($kept->isEmpty() ? 'none' : $kept->map(fn($n, $z) => "{$z} ({$n})")->implode(', ')) . "\n";

if (!$apply) {
    exit("\nNothing changed. Run again with --apply to switch to " . ZONE . ".\n");
}

foreach ($rows as $row) {
    $value = json_decode($row->value, true) ?: [];
    $value['default_time_zone'] = ZONE;
    DB::table('setting_translations')->where('id', $row->id)->update(['value' => json_encode($value)]);
}
$toFix('users')->update(['timezone' => ZONE]);
$toFix('webinars')->update(['timezone' => ZONE]);
Cache::forget('settings.general');

echo "\nDone: default time zone, {$users} users and {$courses} courses are now on " . ZONE . ".\n";
