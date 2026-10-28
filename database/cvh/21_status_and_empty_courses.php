<?php
/**
 * CVH Africa — quick health report of the live data, and hiding published courses that have no content yet.
 *
 *   php database/cvh/21_status_and_empty_courses.php               report only
 *   php database/cvh/21_status_and_empty_courses.php --hide-empty  also set published courses with no lessons to draft
 *   php database/cvh/21_status_and_empty_courses.php --unhide      publish again the courses --hide-empty hid
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$hiddenFile = storage_path('app/cvh_hidden_empty_courses.json');

if (in_array('--unhide', $argv, true)) {
    $ids = is_file($hiddenFile) ? json_decode(file_get_contents($hiddenFile), true) : [];
    DB::table('webinars')->whereIn('id', $ids)->where('status', 'pending')->update(['status' => 'active']);
    @unlink($hiddenFile);
    exit(count($ids) . " course(s) published again.\n");
}

$title = fn($id) => DB::table('webinar_translations')->where('webinar_id', $id)->where('locale', 'en')->value('title');
$contentCount = fn($id) => DB::table('text_lessons')->where('webinar_id', $id)->count()
    + DB::table('files')->where('webinar_id', $id)->count()
    + DB::table('sessions')->where('webinar_id', $id)->count()
    + DB::table('quizzes')->where('webinar_id', $id)->count();

echo "== Old-site import (fspnafri_cvh_24.sql)\n";
if (Schema::hasTable('cvh_import_map')) {
    $map = DB::table('cvh_import_map')->select('source', DB::raw('count(*) as n'))->groupBy('source')->pluck('n', 'source');
    foreach (['users', 'webinars', 'text_lessons', 'quizzes', 'files', 'sales', 'course_learning', 'quizzes_results', 'certificates'] as $k) {
        printf("  %-18s %d\n", $k, $map[$k] ?? 0);
    }
    $oldAdmins = DB::table('cvh_import_map')->join('users', 'users.id', '=', 'cvh_import_map.new_id')
        ->where('source', 'users')->where('users.role_name', 'admin')->pluck('users.email')->implode(', ');
    echo "  old-site admins here: " . ($oldAdmins ?: 'none') . "\n";
} else {
    echo "  NOT RUN YET. Run: php database/cvh/14_import_old_site.php ~/fspnafri_cvh_24.sql\n";
}

echo "\n== Totals\n";
printf("  users %d (students %d, instructors %d, admins %d)\n", DB::table('users')->whereNull('deleted_at')->count(),
    DB::table('users')->where('role_name', 'user')->count(), DB::table('users')->where('role_name', 'teacher')->count(), DB::table('users')->where('role_name', 'admin')->count());
printf("  certificates: %d issued on this site, %d from the old site\n",
    Schema::hasTable('cvh_certificates') ? DB::table('cvh_certificates')->count() : 0, DB::table('certificates')->where('type', 'course')->count());

echo "\n== Published courses\n";
$empty = [];
foreach (DB::table('webinars')->where('status', 'active')->whereNull('deleted_at')->where('private', 0)->orderBy('id')->get(['id', 'slug']) as $course) {
    $n = $contentCount($course->id);
    $videos = DB::table('files')->where('webinar_id', $course->id)->whereIn('storage', ['youtube', 'vimeo', 'upload', 'external_link'])->count();
    printf("  %-4s %-70s %s\n", $n ? 'OK' : 'EMPTY', mb_strimwidth((string)$title($course->id), 0, 70, '…'), $n ? "{$n} items" . ($videos ? ", {$videos} video/file" : '') : 'no lessons');
    if (!$n) $empty[] = $course->id;
}

if (in_array('--hide-empty', $argv, true) && $empty) {
    DB::table('webinars')->whereIn('id', $empty)->update(['status' => 'pending']);
    file_put_contents($hiddenFile, json_encode(array_values(array_unique(array_merge($empty, is_file($hiddenFile) ? json_decode(file_get_contents($hiddenFile), true) : [])))));
    echo "\n" . count($empty) . " empty course(s) set to draft (hidden). Undo: php database/cvh/21_status_and_empty_courses.php --unhide\n";
} elseif ($empty) {
    echo "\n" . count($empty) . " published course(s) have no lessons. Hide them with --hide-empty.\n";
}
