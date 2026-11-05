<?php
/**
 * CVH Africa — fix the header "Create a new course" button for instructors and organizations.
 * It pointed at /panel/webinars/new (the old Rocket LMS address, "Page Not Found" in v2.2);
 * the create-course page is /panel/courses/new. Same as editing it in Admin → Settings → Navbar buttons.
 * Run from the project root:  php database/cvh/37_fix_create_course_button.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$fixed = DB::table('navbar_button_translations')
    ->whereIn('url', ['/panel/webinars/new', 'panel/webinars/new'])
    ->update(['url' => '/panel/courses/new']);

echo "$fixed navbar button link(s) changed to /panel/courses/new\n";

// Any other stored link to the old /panel/webinars/... addresses is listed so it can be checked by hand
foreach (DB::table('navbar_button_translations')->where('url', 'like', '%panel/webinars%')->get() as $row) {
    echo "CHECK navbar button {$row->navbar_button_id} ({$row->locale}): {$row->url}\n";
}
