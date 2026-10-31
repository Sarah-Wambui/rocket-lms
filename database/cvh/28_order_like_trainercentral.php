<?php
/**
 * CVH Africa — check that every course from the old TrainerCentral academy is on this site, and list them in the same
 * order as TrainerCentral's course list (newest first), so they are easy to find.
 *
 *   php database/cvh/28_order_like_trainercentral.php           show each TrainerCentral course: found or MISSING, status, content
 *   php database/cvh/28_order_like_trainercentral.php --apply   put them in TrainerCentral's order
 *
 * The site lists courses (course catalogue, admin list, home page "newest") by creation time, newest first, so the
 * order is set by giving the courses creation times one minute apart, counting down from the newest of them (their
 * dates stay where they were; courses created on this site later still come first). The two TrainerCentral bundles
 * (INNOECOFOOD, HealthyDiets4Africa) are bundles here and are ordered the same way among the bundles.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// TrainerCentral → Courses, sorted by created time (newest first), as on admin.cvh.africa on 31 Oct 2026
const ORDER = [
    'hd4a-agribusiness-agroinnovators-mentorship-session-9',
    'hd4a-agribusiness-agroinnovators-mentorship-session-8',
    'hd4a-agribusiness-agroinnovators-mentorship-session-7',
    'block-of-chain-agroinnovators-onboarding',
    'hd4a-agribusiness-agroinnovators-mentorship-session-6',
    'hd4a-agribusiness-agroinnovators-mentorship-session-5',
    'hd4a-agribusiness-agroinnovators-mentorship-session-3',
    'hd4a-agribusiness-agroinnovators-mentorship-session-2',
    'hd4a-agribusiness-agroinnovators-mentorship-sessions-kick-off',
    'managing-business-start-up',
    'lesson-13-fundraising-and-pitch-deck',
    'lesson-12-food-production-processing-ethics-compliance-and-best-practices-part-4',
    'lesson-11-food-production-processing-ethics-compliance-and-best-practices-part-3',
    'basic-introduction-to-agro-entrepreneurship',
    'food-production-processing-ethics-and-best-practices-part-2',
    'food-production-processing-ethics-and-best-practices-part-1',
    'strategic-financial-management-for-start-ups',
    'lesson-7-legal-and-compliance',
    'business-operations-part-3',
    'business-operations-part-2',
    'business-operations-part-1',
    'reducing-heavy-metal-exposure-in-foods',
    'irish-potato-production',
    'acrylamide-occurrence-in-foods-health-concerns-and-mitigation-strategies',
    'innoecofood',
    'healthydiets4africa',
    'mycotoxins-in-foods-the-health-risk-and-preventive-measures',
    'design-thinking-for-food-system-innovations',
    'maize-production',
    'cabbage-production-in-kenya',
    'climate-change-and-agriculture',
];
const BUNDLES = ['innoecofood', 'healthydiets4africa'];
// TrainerCentral courses the old Rocket site already had under another address (as in 23_import_trainercentral.php)
const SAME_AS = ['irish-potato-production' => 'irish-potato-production-course-2', 'maize-production' => 'a-comprehensive-guide-to-maize-production'];

$apply = in_array('--apply', $argv, true);

$find = function ($slug) {
    $table = in_array($slug, BUNDLES) ? 'bundles' : 'webinars';
    foreach (array_filter([SAME_AS[$slug] ?? null, $slug]) as $candidate) {
        $row = DB::table($table)->where('slug', $candidate)->first(['id', 'slug', 'status', 'created_at']);
        if ($row) return [$table, $row];
    }
    // slugs here may differ only in letter case
    $row = DB::table($table)->whereRaw('lower(slug) = ?', [strtolower(SAME_AS[$slug] ?? $slug)])->first(['id', 'slug', 'status', 'created_at']);
    return [$table, $row];
};
$title = fn($table, $id) => DB::table($table === 'bundles' ? 'bundle_translations' : 'webinar_translations')
    ->where($table === 'bundles' ? 'bundle_id' : 'webinar_id', $id)->orderByRaw("locale = 'en' desc")->value('title');
$content = function ($table, $id) {
    if ($table === 'bundles') {
        return DB::table('bundle_webinars')->where('bundle_id', $id)->count() . ' courses';
    }
    $chapters = DB::table('webinar_chapters')->where('webinar_id', $id)->pluck('id');
    return count($chapters) . ' sections, ' . DB::table('webinar_chapter_items')->whereIn('chapter_id', $chapters)->count() . ' items';
};

$found = [];
$missing = 0;
foreach (ORDER as $i => $slug) {
    [$table, $row] = $find($slug);
    if (!$row) {
        $missing++;
        printf("%2d  MISSING  %s\n", $i + 1, $slug);
        continue;
    }
    $found[] = [$table, $row];
    printf("%2d  %-7s  %-6s  %-8s  %-22s  %s\n", $i + 1, 'ok', $table === 'bundles' ? 'bundle' : 'course', $row->status, $content($table, $row->id), mb_strimwidth((string)$title($table, $row->id), 0, 70, '…'));
}
echo "\n" . count($found) . " of " . count(ORDER) . " TrainerCentral courses are on the site" . ($missing ? ", {$missing} MISSING" : '') . ".\n";

if (!$apply) {
    exit("Order not changed. Run again with --apply to list them in TrainerCentral's order.\n");
}

// newest first: the first one gets the newest time of the group, each next one a minute earlier
foreach (['webinars', 'bundles'] as $table) {
    $rows = array_values(array_filter($found, fn($f) => $f[0] === $table));
    if (!$rows) continue;
    $time = max(array_map(fn($f) => (int)$f[1]->created_at, $rows));
    foreach ($rows as [, $row]) {
        DB::table($table)->where('id', $row->id)->update(['created_at' => $time]);
        $time -= 60;
    }
}
Cache::flush();

echo "Done: courses and bundles are now listed in TrainerCentral's order.\n";
