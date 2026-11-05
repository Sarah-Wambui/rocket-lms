<?php
/**
 * CVH Africa — give the HD4A mentorship sessions that came from TrainerCentral without a cover
 * (Kick-Off, Sessions 2, 3, 5, 8 ...) the shared "HealthyDiets4Africa Agro-business Mentorship" cover.
 * Image: public/store/cvh/courses/mentorship-session.jpg (copy of the Session 6 cover, 810x540).
 * Only courses whose image is empty or points at a file that does not exist are changed.
 * Any other course without an image falls back to a CVH photo in Webinar::getImage().
 * Run from the project root:  php database/cvh/36_mentorship_covers.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cover = '/store/cvh/courses/mentorship-session.jpg';
if (!is_file(public_path(ltrim($cover, '/')))) {
    exit("MISSING image $cover (pull the latest code first)\n");
}

$hasImage = fn($path) => !empty($path) && is_file(public_path(ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/')));

$sessions = DB::table('webinars')
    ->where('slug', 'like', 'hd4a-agribusiness-agroinnovators-mentorship%')
    ->whereNull('deleted_at')
    ->get(['id', 'slug', 'thumbnail', 'image_cover']);

$changed = 0;
foreach ($sessions as $s) {
    $update = [];
    if (!$hasImage($s->thumbnail)) $update['thumbnail'] = $cover;
    if (!$hasImage($s->image_cover)) $update['image_cover'] = $cover;

    if ($update) {
        DB::table('webinars')->where('id', $s->id)->update($update + ['updated_at' => time()]);
        echo "OK   {$s->slug}\n";
        $changed++;
    }
}

echo "{$sessions->count()} mentorship sessions found, $changed given the mentorship cover.\n";
