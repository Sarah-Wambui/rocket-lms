<?php
/**
 * CVH Africa — remove the Rocket LMS demo content that shipped with the script, keep what came from the old site.
 *
 *   php database/cvh/22_remove_demo_data.php           show what would be deleted
 *   php database/cvh/22_remove_demo_data.php --apply   delete it (take a database backup first)
 *
 * Demo = created before 2026 (the template's seed dates are 2020-2025) and not brought in by 14_import_old_site.php
 * (cvh_import_map). Kept: the admin (id 1), everyone/everything imported from the old site, and what CVH created on
 * this site (the TrainerCentral course pages, instructors, bundles, blog posts, new sign-ups — all dated 2026+).
 * Rows that hang off deleted users/courses (their sales, reviews, comments, chapters, ...) go with them through the
 * database's ON DELETE CASCADE foreign keys.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CUTOFF = 1767225600; // 2026-01-01

if (!Schema::hasTable('cvh_import_map')) {
    exit("The old-site import has not run here (no cvh_import_map). Run 14_import_old_site.php first, or nothing imported would be told apart from demo.\n");
}

$apply = in_array('--apply', $argv, true);
$imported = fn($source) => DB::table('cvh_import_map')->where('source', $source)->pluck('new_id')->all();
$importedUsers = $imported('users');
$importedCourses = $imported('webinars');

$users = DB::table('users')->where('id', '<>', 1)->where('created_at', '<', CUTOFF)->whereNotIn('id', $importedUsers)->get(['id', 'full_name', 'email', 'role_name']);
$userIds = $users->pluck('id')->all();
$courses = DB::table('webinars')->where('created_at', '<', CUTOFF)->whereNotIn('id', $importedCourses)->get(['id', 'slug']);

// never delete a demo user who still teaches or owns a course we keep
$keptCourses = DB::table('webinars')->whereNotIn('id', $courses->pluck('id'));
$stillNeeded = $keptCourses->clone()->whereIn('creator_id', $userIds)->pluck('creator_id')
    ->merge($keptCourses->clone()->whereIn('teacher_id', $userIds)->pluck('teacher_id'))->unique()->all();
$users = $users->reject(fn($u) => in_array($u->id, $stillNeeded));

$other = [];
foreach (['bundles' => 'slug', 'products' => 'slug', 'blog' => 'slug', 'upcoming_courses' => 'slug', 'forum_topics' => 'title'] as $table => $label) {
    $other[$table] = DB::table($table)->where('created_at', '<', CUTOFF)->pluck($label, 'id');
}

echo "== Demo users (" . $users->count() . ")\n";
foreach ($users as $u) printf("  %-5d %-12s %-28s %s\n", $u->id, $u->role_name, mb_strimwidth($u->full_name, 0, 28, '…'), $u->email);
if ($stillNeeded) echo "  kept because they teach a kept course: " . implode(', ', $stillNeeded) . "\n";

echo "\n== Demo courses (" . $courses->count() . ")\n";
foreach ($courses as $c) echo "  {$c->id} {$c->slug}\n";

foreach ($other as $table => $rows) {
    echo "\n== Demo {$table} (" . $rows->count() . ")\n";
    foreach ($rows as $id => $label) echo "  {$id} " . mb_strimwidth((string)$label, 0, 80, '…') . "\n";
}

$demoSales = DB::table('sales')->where(fn($q) => $q->whereIn('webinar_id', $courses->pluck('id'))->orWhereIn('buyer_id', $users->pluck('id')))->count();
echo "\nSales/enrolments going with them: {$demoSales} (none of them from the old site's real learners)\n";

// imported from the old site but look like the template's demo accounts: listed for a decision, not deleted
$suspect = DB::table('users')->whereIn('id', $importedUsers)->where(fn($q) => $q->where('email', 'like', '%@demo.com'))->get(['id', 'full_name', 'email']);
if ($suspect->isNotEmpty()) {
    echo "\nNote — these came over from the old site but use demo addresses; kept (delete by hand in Admin → Users if they are not real):\n";
    foreach ($suspect as $u) echo "  {$u->id} {$u->full_name} <{$u->email}>\n";
}

if (!$apply) {
    exit("\nNothing deleted. Back up the database, then run again with --apply.\n");
}

DB::transaction(function () use ($courses, $users, $other) {
    foreach ($other as $table => $rows) DB::table($table)->whereIn('id', $rows->keys())->delete();
    DB::table('webinars')->whereIn('id', $courses->pluck('id'))->delete();
    DB::table('users')->whereIn('id', $users->pluck('id'))->delete();
});
Cache::flush();

echo "\nDone: deleted {$users->count()} demo users, {$courses->count()} demo courses and the demo "
    . collect($other)->map(fn($r, $t) => $r->count() . " {$t}")->implode(', ') . ".\n";
