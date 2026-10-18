<?php
/**
 * CVH Africa — client feedback (Oct 2026):
 *  1. Colours: back to the template's original blue/navy with white; CVH green only as the accent colour.
 *  2. Homepage: add a partner-logo strip after "Featured Courses".
 * Run from the project root:  php database/cvh/03_blue_theme_and_partners.php   (re-runnable)
 * To go back to the all-green palette: set themes.color_id to the "CVH Green" row.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const CVH = '/store/cvh';
$now = time();

DB::transaction(function () use ($now) {

    /* ---------- 1. Colours: template blue + CVH green accent ---------- */
    $colors = [
        'light' => [
            'primary' => '#0170ff', 'primary_saturated' => '#67a9ff', 'secondary' => '#0e2145', 'accent' => '#5eac49',
            'success' => '#3fcd82', 'info' => '#67a9ff', 'warning' => '#ffa200', 'danger' => '#f63c3c',
            'dark' => '#121f3e', 'black' => '#000000', 'white' => '#ffffff',
            'gray_100' => '#fafcff', 'gray_200' => '#f0f4f9', 'gray_300' => '#e9edf3', 'gray_400' => '#cdd5e2',
            'gray_500' => '#97a7bf', 'gray' => '#f5f8f9', 'section_bg' => '#eaf0f3',
        ],
        'dark' => [
            'primary' => '#3e93ff', 'primary_saturated' => '#8dbeff', 'secondary' => '#2658b7', 'accent' => '#6fc25a',
            'success' => '#5ade98', 'info' => '#8dbeff', 'warning' => '#ffb32d', 'danger' => '#fe6363',
            'dark' => '#aab8c5', 'black' => '#e1eaf6', 'white' => '#1e1f26',
            'gray_100' => '#272832', 'gray_200' => '#30313e', 'gray_300' => '#3e404e', 'gray_400' => '#5d5f72',
            'gray_500' => '#8391a2', 'gray' => '#17181e', 'section_bg' => '#2d323a',
        ],
    ];
    $colorId = DB::table('theme_colors_fonts')->where('type', 'color')->where('title', 'CVH Blue & Green')->value('id')
        ?? DB::table('theme_colors_fonts')->insertGetId(['type' => 'color', 'title' => 'CVH Blue & Green', 'content' => '', 'created_at' => $now]);
    DB::table('theme_colors_fonts')->where('id', $colorId)->update(['content' => json_encode($colors)]);
    DB::table('themes')->where('is_default', 1)->update(['color_id' => $colorId]);

    /* ---------- Restore phase-2 navigation (an earlier re-run of 01 had reset it) ---------- */
    $nav = [
        ['Home', '/'], ['Courses', '/classes'], ['Projects', '/bundles'], ['Innovations', '/pages/innovations'], ['Forums', '/forums'],
        ['Instructors', '/instructors'], ['Blog', '/blog'], ['About', '/pages/about'],
    ];
    $header = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 1)->where('locale', 'en')->first();
    $hc = json_decode($header->content, true);
    $hc['specific_links'] = [];
    foreach ($nav as $i => [$t, $u]) {
        $hc['specific_links']["cvhA{$i}"] = ['title' => $t, 'url' => $u];
    }
    $hc['specific_links']['record'] = ['title' => null, 'url' => null];
    DB::table('theme_header_footer_translations')->where('id', $header->id)->update(['content' => json_encode($hc)]);

    $navSetting = [];
    foreach ($nav as $i => [$t, $u]) {
        $navSetting["cvh0{$i}"] = ['title' => $t, 'link' => $u, 'order' => (string)($i + 1)];
    }
    DB::table('setting_translations')->where('setting_id', DB::table('settings')->where('name', 'navbar_links')->value('id'))
        ->where('locale', 'en')->update(['value' => json_encode($navSetting)]);

    $footer = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 2)->where('locale', 'en')->first();
    $fc = json_decode($footer->content, true);
    $fc['specific_links'] = [
        'cvhF1' => ['title' => 'About us', 'url' => '/pages/about'],
        'cvhF2' => ['title' => 'HD4A Project', 'url' => '/bundles/HealthyDiets4Africa'],
        'cvhF3' => ['title' => 'INNOECOFOOD Project', 'url' => '/bundles/INNOECOFOOD'],
        'cvhF4' => ['title' => 'Innovations', 'url' => '/pages/innovations'],
        'cvhF5' => ['title' => 'Certificate Validation', 'url' => '/certificate_validation'],
        'cvhF6' => ['title' => 'Contact', 'url' => '/contact'],
        'cvhF7' => ['title' => 'Terms of use', 'url' => '/pages/terms'],
        'record' => ['title' => null, 'url' => null],
    ];
    DB::table('theme_header_footer_translations')->where('id', $footer->id)->update(['content' => json_encode($fc)]);

    $hero = DB::table('landing_component_translations')->where('landing_component_id', 1)->where('locale', 'en')->first();
    $heroContent = json_decode($hero->content, true);
    $heroContent['upper_cta']['url'] = '/bundles/HealthyDiets4Africa';
    DB::table('landing_component_translations')->where('id', $hero->id)->update(['content' => json_encode($heroContent)]);

    /* ---------- 2. Partners strip on the homepage ---------- */
    $componentTypeId = DB::table('landing_builder_components')->where('name', 'cvh_partners')->value('id')
        ?? DB::table('landing_builder_components')->insertGetId(['name' => 'cvh_partners', 'category' => 'logos']);

    $landingId = DB::table('themes')->where('is_default', 1)->value('home_landing_id');
    $existing = DB::table('landing_components')->where('landing_id', $landingId)->where('component_id', $componentTypeId)->first();

    if (!$existing) {
        // Insert right after "Featured Courses" (and its spacer): partners, then a spacer.
        $featuredTypeId = DB::table('landing_builder_components')->where('name', 'featured_courses')->value('id');
        $spacerTypeId = DB::table('landing_builder_components')->where('name', 'vertical_spacer')->value('id');
        $after = DB::table('landing_components')->where('landing_id', $landingId)->where('component_id', $featuredTypeId)->value('order') + 1; // featured's spacer
        DB::table('landing_components')->where('landing_id', $landingId)->where('order', '>', $after)->increment('order', 2);

        $partnersId = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $componentTypeId, 'preview' => null, 'enable' => 1, 'order' => $after + 1,
        ]);
        $spacerId = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $spacerTypeId, 'preview' => null, 'enable' => 1, 'order' => $after + 2,
        ]);
        // Spacers carry their own content; copy it from an existing spacer.
        $spacerContent = DB::table('landing_component_translations as t')
            ->join('landing_components as c', 'c.id', '=', 't.landing_component_id')
            ->where('c.component_id', $spacerTypeId)->where('t.locale', 'en')->value('t.content');
        if ($spacerContent) {
            DB::table('landing_component_translations')->insert(['landing_component_id' => $spacerId, 'locale' => 'en', 'content' => $spacerContent]);
        }
    } else {
        $partnersId = $existing->id;
    }

    $logos = [];
    foreach (require __DIR__ . '/partners.php' as $i => [$file, $name]) {
        $logos['p' . str_pad($i, 2, '0', STR_PAD_LEFT)] = [
            'image' => CVH . '/' . implode('/', array_map('rawurlencode', explode('/', $file))),
            'name' => $name,
        ];
    }
    DB::table('landing_component_translations')->updateOrInsert(
        ['landing_component_id' => $partnersId, 'locale' => 'en'],
        ['content' => json_encode([
            'main_content' => [
                'pre_title' => 'Our Partners',
                'title' => 'Who We Work With',
                'description' => 'We collaborate with over 41 partners globally to develop, co-create and review our courses — from research institutes and universities to food system innovators.',
            ],
            'logos' => $logos,
            'button' => ['label' => 'Collaborate with Us', 'url' => '/contact'],
        ])]
    );
});

echo "CVH blue theme + partners strip applied.\n";
