<?php
/**
 * CVH Africa — menu like the original demo, and every homepage category filled:
 *  1. Events: turns the template's Events feature on (its admin settings page is a paid-plugin placeholder),
 *     so /events works and instructors/admin can create events from their dashboard (Dashboard → Events).
 *  2. Jobs: a "Coming soon" page at /pages/jobs (edit it in Admin → Pages).
 *  3. Main menu: "Events" and "Jobs" after "Forums"; the header CSS from 07 is replaced so 10 links fit.
 *  4. Categories: an active course outside the four CVH categories is placed in one by its title, then the
 *     number of active courses per category is printed (a category showing 0 needs a course added in the admin).
 * Run from the project root:  php database/cvh/25_events_jobs_categories.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CVH = '/store/cvh';
$now = time();

// Rebuilds a links array with $new (key => link) inserted right after the entry whose url is $afterUrl; links already there are skipped.
function insertLinksAfter(array $links, string $afterUrl, array $new, string $urlKey): array
{
    $present = array_filter(array_map(fn($l) => is_array($l) ? ($l[$urlKey] ?? null) : null, $links));
    $new = array_filter($new, fn($l) => !in_array($l[$urlKey], $present, true));
    if (!$new) {
        return $links;
    }
    $out = [];
    $placed = false;
    foreach ($links as $k => $l) {
        if ($k === 'record') continue;
        $out[$k] = $l;
        if (is_array($l) && ($l[$urlKey] ?? null) === $afterUrl) {
            $out += $new;
            $placed = true;
        }
    }
    if (!$placed) $out += $new;
    if (array_key_exists('record', $links)) $out['record'] = $links['record'];
    return $out;
}

function saveSetting(string $name, string $page, callable $mutate): void
{
    $settingId = DB::table('settings')->where('name', $name)->value('id')
        ?? DB::table('settings')->insertGetId(['name' => $name, 'page' => $page, 'updated_at' => time()]);
    $row = DB::table('setting_translations')->where('setting_id', $settingId)->where('locale', 'en')->first();
    $value = $mutate($row ? (json_decode($row->value, true) ?: []) : []);
    DB::table('setting_translations')->updateOrInsert(['setting_id' => $settingId, 'locale' => 'en'], ['value' => json_encode($value)]);
}

DB::transaction(function () use ($now) {
    /* ---------- 1. Events on ---------- */
    saveSetting('events_settings', 'general', fn($v) => array_merge(['event_recent_reviews_status' => '1', 'qr_status' => '0'], $v, ['status' => '1']));

    /* ---------- 2. Jobs: coming soon ---------- */
    $html = '<div style="text-align:center;padding:48px 16px;">'
        . '<p style="display:inline-block;padding:6px 16px;border-radius:8px;background:var(--primary);color:#fff;font-size:13px;font-weight:600;">Coming soon</p>'
        . '<h2 style="margin-top:20px;">Jobs and opportunities for food system talent</h2>'
        . '<p style="max-width:640px;margin:16px auto 0;">We are building a job board where CVH learners can find roles, internships and opportunities with our partners across Africa\'s food systems. Check back soon.</p>'
        . '<p style="margin-top:32px;"><a href="/classes" style="display:inline-block;background:var(--primary);color:#fff;padding:12px 28px;border-radius:12px;text-decoration:none;font-weight:600;">Explore Courses</a></p>'
        . '</div>';
    $page = ['name' => 'Jobs', 'robot' => 0, 'status' => 'publish'];
    foreach (['title' => 'Jobs', 'content' => $html] as $column => $value) {
        if (Schema::hasColumn('pages', $column)) $page[$column] = $value; // older schema kept these on pages
    }
    $pageId = DB::table('pages')->where('link', '/jobs')->value('id');
    if ($pageId) {
        DB::table('pages')->where('id', $pageId)->update($page);
    } else {
        $pageId = DB::table('pages')->insertGetId($page + ['link' => '/jobs', 'created_at' => $now]);
    }
    DB::table('page_translations')->updateOrInsert(['page_id' => $pageId, 'locale' => 'en'], [
        'title' => 'Jobs',
        'subtitle' => 'Coming soon',
        'seo_description' => 'Jobs and opportunities for CVH learners across Africa\'s food systems: coming soon.',
        'content' => $html,
    ]);

    /* ---------- 3. Menu ---------- */
    $header = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 1)->where('locale', 'en')->first();
    $c = json_decode($header->content, true);
    $c['specific_links'] = insertLinksAfter($c['specific_links'] ?? [], '/community', [
        'cvhEvents' => ['title' => 'Events', 'url' => '/events'],
        'cvhJobs' => ['title' => 'Jobs', 'url' => '/pages/jobs'],
    ], 'url');
    DB::table('theme_header_footer_translations')->where('id', $header->id)->update(['content' => json_encode($c)]);

    saveSetting('navbar_links', 'navbar_links', function ($links) {
        $links = insertLinksAfter($links, '/community', [
            'cvhEvents' => ['title' => 'Events', 'link' => '/events', 'order' => '0'],
            'cvhJobs' => ['title' => 'Jobs', 'link' => '/pages/jobs', 'order' => '0'],
        ], 'link');
        $i = 1;
        foreach ($links as $k => $l) {
            if ($k !== 'record') $links[$k]['order'] = (string)$i++;
        }
        return $links;
    });

    // Header fit for 10 links: below 1400px About and Contact hide, below 1200px also Blog and Jobs (all stay in the footer or menu on mobile).
    $css = <<<'CSS'
/* CVH:header-menu:start */
@media (min-width: 992px) {
  .theme-header-1__main .row > div:nth-child(2),
  .theme-header-1__main .row > div:nth-child(4) { flex: 0 0 auto; width: auto; max-width: none; }
  .theme-header-1__main .row > div:nth-child(3) { flex: 1 1 0; width: auto; max-width: none; min-width: 0; }
  .theme-header-1__main .row > div:nth-child(3) > div { justify-content: center; white-space: nowrap; gap: 24px !important; }
}
@media (min-width: 992px) and (max-width: 1399.98px) {
  .theme-header-1__main .row > div:nth-child(2) { display: none !important; }
  .theme-header-1__main .row > div:nth-child(3) > div { gap: 16px !important; }
  .theme-header-1__main .row > div:nth-child(3) > div > a[href*="/pages/about"],
  .theme-header-1__main .row > div:nth-child(3) > div > a[href$="/contact"] { display: none; }
}
@media (min-width: 992px) and (max-width: 1199.98px) {
  .theme-header-1__main .row > div:nth-child(3) > div { gap: 14px !important; font-size: 14px; }
  .theme-header-1__main .row > div:nth-child(3) > div > a[href$="/blog"],
  .theme-header-1__main .row > div:nth-child(3) > div > a[href*="/pages/jobs"] { display: none; }
}
/* CVH:header-menu:end */
CSS;
    $theme = DB::table('themes')->where('is_default', 1)->first();
    $contents = json_decode($theme->contents, true);
    $existing = preg_replace('#/\* CVH:header-menu:start \*/.*?/\* CVH:header-menu:end \*/\s*#s', '', $contents['custom_css'] ?? '');
    $contents['custom_css'] = trim($existing . "\n" . $css);
    DB::table('themes')->where('id', $theme->id)->update(['contents' => json_encode($contents)]);

    /* ---------- 4. Categories ---------- */
    $cat = DB::table('categories')->whereIn('slug', ['Food-Safety', 'Agripreneurship', 'Climate-and-Agriculture', 'Aquaculture'])->pluck('id', 'slug');
    $byTitle = function (string $title) use ($cat) {
        if (preg_match('/aqua|fish|insect|spirulina|circular/i', $title)) return $cat['Aquaculture'];
        if (preg_match('/climate|sustainab|environment|soil|water/i', $title)) return $cat['Climate-and-Agriculture'];
        if (preg_match('/food|safety|nutri|diet|toxin|metal|hygiene|acrylamide|processing/i', $title)) return $cat['Food-Safety'];
        return $cat['Agripreneurship'];
    };
    $stray = DB::table('webinars as w')->leftJoin('webinar_translations as t', fn($j) => $j->on('t.webinar_id', '=', 'w.id')->where('t.locale', 'en'))
        ->where('w.status', 'active')
        ->where(fn($q) => $q->whereNull('w.category_id')->orWhereNotIn('w.category_id', $cat->values()))
        ->get(['w.id', 'w.slug', 't.title']);
    foreach ($stray as $course) {
        $categoryId = $byTitle($course->title ?? $course->slug);
        DB::table('webinars')->where('id', $course->id)->update(['category_id' => $categoryId]);
        echo "  placed \"" . ($course->title ?? $course->slug) . "\" in " . $cat->flip()[$categoryId] . "\n";
    }

    echo "Active courses per category:\n";
    foreach ($cat as $slug => $id) {
        printf("  %-26s %d\n", $slug, DB::table('webinars')->where('category_id', $id)->where('status', 'active')->count());
    }
});

// Settings are cached for a day; drop the ones changed here so they apply now.
foreach (['events_settings', 'navbar_links'] as $name) {
    cache()->forget('settings.' . $name);
}

echo "Events on, Jobs coming-soon page, menu updated.\n";
