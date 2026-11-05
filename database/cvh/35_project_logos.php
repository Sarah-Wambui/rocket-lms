<?php
/**
 * CVH Africa — use each project's own logo as its card image on /bundles ("Project Courses").
 *   HealthyDiets4Africa → HD4A logo (from https://hd4a.eu/)
 *   INNOECOFOOD         → INNOECOFOOD logo (from https://innoecofood.eu/)
 * Images: public/store/cvh/courses/bundle-hd4a-logo.jpg, bundle-innoecofood-logo.jpg (978x678, logo on white).
 * Only the card thumbnail changes; the wide cover photo on each project page stays.
 * Run from the project root:  php database/cvh/35_project_logos.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$logos = [
    'HealthyDiets4Africa' => '/store/cvh/courses/bundle-hd4a-logo.jpg',
    'INNOECOFOOD' => '/store/cvh/courses/bundle-innoecofood-logo.jpg',
];

foreach ($logos as $slug => $path) {
    if (!is_file(public_path(ltrim($path, '/')))) {
        echo "MISSING image $path (pull the latest code first)\n";
        continue;
    }

    $updated = DB::table('bundles')->where('slug', $slug)->update(['thumbnail' => $path, 'updated_at' => time()]);
    echo $updated ? "OK   $slug -> $path\n" : "SKIP $slug: no bundle with this slug\n";
}
