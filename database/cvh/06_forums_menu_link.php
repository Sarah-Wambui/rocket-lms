<?php
/**
 * CVH Africa — add "Forums" to the main menu (after "Innovations").
 * Note: /forums shows "Access Not Allowed" until the Forum feature (Plugins Bundle) is enabled.
 * Run from the project root:  php database/cvh/06_forums_menu_link.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Rebuilds a links array, inserting $new right after the entry whose url is $afterUrl (skipped if already present).
function insertLinkAfter(array $links, string $afterUrl, array $new, string $urlKey): array
{
    foreach ($links as $l) {
        if (is_array($l) && ($l[$urlKey] ?? null) === $new[$urlKey]) {
            return $links;
        }
    }
    $out = [];
    foreach ($links as $k => $l) {
        if ($k === 'record') continue;
        $out[$k] = $l;
        if (is_array($l) && ($l[$urlKey] ?? null) === $afterUrl) {
            $out['cvhForums'] = $new;
        }
    }
    if (!isset($out['cvhForums'])) $out['cvhForums'] = $new;
    if (array_key_exists('record', $links)) $out['record'] = $links['record'];
    return $out;
}

DB::transaction(function () {
    // Header menu
    $header = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 1)->where('locale', 'en')->first();
    $c = json_decode($header->content, true);
    $c['specific_links'] = insertLinkAfter($c['specific_links'] ?? [], '/pages/innovations', ['title' => 'Forums', 'url' => '/forums'], 'url');
    DB::table('theme_header_footer_translations')->where('id', $header->id)->update(['content' => json_encode($c)]);

    // Legacy navbar_links setting (used by some views/mobile), kept in sync with renumbered order
    $settingId = DB::table('settings')->where('name', 'navbar_links')->value('id');
    $row = DB::table('setting_translations')->where('setting_id', $settingId)->where('locale', 'en')->first();
    $links = insertLinkAfter(json_decode($row->value, true) ?: [], '/pages/innovations', ['title' => 'Forums', 'link' => '/forums', 'order' => '0'], 'link');
    $i = 1;
    foreach ($links as $k => $l) {
        $links[$k]['order'] = (string)$i++;
    }
    DB::table('setting_translations')->where('id', $row->id)->update(['value' => json_encode($links)]);
});

echo "Forums link added to the menu.\n";
