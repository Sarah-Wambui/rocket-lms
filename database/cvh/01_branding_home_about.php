<?php
/**
 * CVH Africa rebrand — phase 1: branding, header/footer, contact, categories, homepage, About page.
 * Run from the project root:  php database/cvh/01_branding_home_about.php
 * Re-runnable: inserts are guarded, updates overwrite with the same values.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const CVH = '/store/cvh';
$now = time();

function updateSetting(string $name, callable $mutate, string $locale = 'en'): void
{
    $settingId = DB::table('settings')->where('name', $name)->value('id');
    $row = DB::table('setting_translations')->where('setting_id', $settingId)->where('locale', $locale)->first();
    $value = $mutate($row ? json_decode($row->value, true) : []);
    DB::table('setting_translations')->updateOrInsert(
        ['setting_id' => $settingId, 'locale' => $locale],
        ['value' => json_encode($value)]
    );
}

function updateHeaderFooter(int $id, callable $mutate): void
{
    $row = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', $id)->where('locale', 'en')->first();
    DB::table('theme_header_footer_translations')->where('id', $row->id)
        ->update(['content' => json_encode($mutate(json_decode($row->content, true)))]);
}

function updateLandingComponent(int $id, callable $mutate): void
{
    $row = DB::table('landing_component_translations')->where('landing_component_id', $id)->where('locale', 'en')->first();
    DB::table('landing_component_translations')->where('id', $row->id)
        ->update(['content' => json_encode($mutate(json_decode($row->content, true)))]);
}

// True when a links array was already set up by these CVH scripts (keys start with "cvh"),
// so re-running this file never overwrites navigation that later scripts or the admin changed.
function hasCvhKeys($links): bool
{
    return is_array($links) && (bool)array_filter(array_keys($links), fn($k) => str_starts_with((string)$k, 'cvh'));
}

function key5(): string
{
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
}

DB::transaction(function () use ($now) {

    /* ---------- Theme: colours & font ---------- */
    $colors = [
        'light' => [
            'primary' => '#5eac49', 'primary_saturated' => '#8cc97b', 'secondary' => '#165a16', 'accent' => '#ff8a38',
            'success' => '#2db45c', 'info' => '#68b5e2', 'warning' => '#ffa200', 'danger' => '#f63c3c',
            'dark' => '#153520', 'black' => '#000000', 'white' => '#ffffff',
            'gray_100' => '#fafdf9', 'gray_200' => '#f1f6ef', 'gray_300' => '#e7eee5', 'gray_400' => '#cfdacb',
            'gray_500' => '#97a893', 'gray' => '#f5f9f4', 'section_bg' => '#eaf3e7',
        ],
        'dark' => [
            'primary' => '#6fc25a', 'primary_saturated' => '#9bd78b', 'secondary' => '#2f8a2f', 'accent' => '#ff9d57',
            'success' => '#5ade98', 'info' => '#8dc8ec', 'warning' => '#ffb32d', 'danger' => '#fe6363',
            'dark' => '#b5c8b8', 'black' => '#e3eee5', 'white' => '#1d221e',
            'gray_100' => '#262c27', 'gray_200' => '#2f3630', 'gray_300' => '#3d453e', 'gray_400' => '#5c665d',
            'gray_500' => '#879587', 'gray' => '#171b18', 'section_bg' => '#2b332c',
        ],
    ];
    $colorId = DB::table('theme_colors_fonts')->where('type', 'color')->where('title', 'CVH Green')->value('id')
        ?? DB::table('theme_colors_fonts')->insertGetId(['type' => 'color', 'title' => 'CVH Green', 'content' => '', 'created_at' => $now]);
    DB::table('theme_colors_fonts')->where('id', $colorId)->update(['content' => json_encode($colors)]);

    $fonts = [
        'main' => ['regular' => CVH . '/fonts/poppins-regular.woff2', 'bold' => CVH . '/fonts/poppins-bold.woff2', 'medium' => CVH . '/fonts/poppins-medium.woff2'],
        'rtl' => ['regular' => '/store/1/fonts/Tajawal-Regular.woff2', 'bold' => '/store/1/fonts/Tajawal-Bold.woff2', 'medium' => '/store/1/fonts/Tajawal-Medium.woff2'],
    ];
    $fontId = DB::table('theme_colors_fonts')->where('type', 'font')->where('title', 'Poppins (CVH)')->value('id')
        ?? DB::table('theme_colors_fonts')->insertGetId(['type' => 'font', 'title' => 'Poppins (CVH)', 'content' => '', 'created_at' => $now]);
    DB::table('theme_colors_fonts')->where('id', $fontId)->update(['content' => json_encode($fonts)]);

    $theme = DB::table('themes')->where('is_default', 1)->first();
    $contents = json_decode($theme->contents, true);
    $contents['authentication_pages']['slider_contents'] = [
        'uHuuS' => ['title' => 'Free, Certified Courses', 'subtitle' => 'Earn a digital certificate for every course you complete', 'image' => '/store/1/themes/general/authentication_slide1.png'],
        'axuTg' => ['title' => 'Learn from Food System Experts', 'subtitle' => 'Courses co-created with 41+ partners across the food system', 'image' => '/store/1/themes/general/authentication_slide2.png'],
        'RuDfz' => ['title' => 'Grow Your Skills and Career', 'subtitle' => 'Practical food safety, nutrition and agribusiness skills', 'image' => '/store/1/themes/general/authentication_slide3.png'],
    ];
    DB::table('themes')->where('id', $theme->id)->update([
        'color_id' => $colorId, 'font_id' => $fontId, 'contents' => json_encode($contents),
    ]);

    /* ---------- General settings ---------- */
    updateSetting('general', fn($v) => array_merge($v, [
        'site_name' => 'CVH Africa',
        'site_email' => 'cvh@fspnafrica.org',
        'site_phone' => '+254 748 464853',
        'site_language' => 'EN',
        'user_languages' => ['EN'],
        'default_time_zone' => 'Africa/Nairobi',
        'fav_icon' => CVH . '/brand/favicon.png',
        'logo' => CVH . '/brand/cvh-logo.png',
        'dark_mode_logo' => CVH . '/brand/cvh-logo-dark-mode.png',
    ]));

    updateSetting('seo_metas', function ($v) {
        $set = [
            'home' => ['Home', 'CVH Africa — the Community Virtual Hub. Free online courses on food safety, nutrition, agribusiness and sustainable food systems for Africa.'],
            'about' => ['About Us', 'The Community Virtual Hub is an EU-funded e-learning platform coordinated by Food Security for Peace and Nutrition Africa.'],
            'contact' => ['Contact', 'Get in touch with the CVH Africa team in Nairobi.'],
            'classes' => ['Courses', 'Explore free food system courses on food safety, agripreneurship, climate and aquaculture.'],
            'blog' => ['Blog', 'News, articles and innovator stories from the CVH community.'],
        ];
        foreach ($set as $k => [$title, $desc]) {
            $v[$k] = array_merge($v[$k] ?? [], ['title' => $title, 'description' => $desc]);
        }
        return $v;
    });

    updateSetting('contact_us', fn($v) => array_merge($v, [
        'phones' => '(+254) 748 464853',
        'emails' => 'cvh@fspnafrica.org',
        'address' => "Africa Alliance YMCA Building, Off State House Avenue\r\nBehind Ethiopian Embassy\r\nNairobi, Kenya",
        'latitude' => '-1.2878',
        'longitude' => '36.8063',
        'map_zoom' => '16',
        'additional_information_title' => 'Get in touch',
        'additional_information_subtitle' => "Email or call us and our team will get back to you.",
    ]));

    updateSetting('navbar_links', fn($v) => hasCvhKeys($v) ? $v : [
        'cvh01' => ['title' => 'Home', 'link' => '/', 'order' => '1'],
        'cvh02' => ['title' => 'Courses', 'link' => '/classes', 'order' => '2'],
        'cvh03' => ['title' => 'Instructors', 'link' => '/instructors', 'order' => '3'],
        'cvh04' => ['title' => 'Blog', 'link' => '/blog', 'order' => '4'],
        'cvh05' => ['title' => 'About us', 'link' => '/pages/about', 'order' => '5'],
        'cvh06' => ['title' => 'Contact', 'link' => '/contact', 'order' => '6'],
    ]);

    /* ---------- Header ---------- */
    updateHeaderFooter(1, function ($c) {
        $c['top_navbar']['phone'] = '(+254) 748 464853';
        $c['top_navbar']['email'] = 'cvh@fspnafrica.org';
        $c['top_navbar']['link_1'] = ['title' => 'Sign In', 'url' => '/login'];
        $c['top_navbar']['link_2'] = ['title' => 'Sign Up', 'url' => '/register'];
        if (!hasCvhKeys($c['specific_links'] ?? null)) $c['specific_links'] = [
            'cvhA1' => ['title' => 'Home', 'url' => '/'],
            'cvhA2' => ['title' => 'Courses', 'url' => '/classes'],
            'cvhA3' => ['title' => 'Instructors', 'url' => '/instructors'],
            'cvhA4' => ['title' => 'Blog', 'url' => '/blog'],
            'cvhA5' => ['title' => 'About us', 'url' => '/pages/about'],
            'cvhA6' => ['title' => 'Contact', 'url' => '/contact'],
            'record' => ['title' => null, 'url' => null],
        ];
        $c['specific_buttons']['BFKTm']['title'] = 'Explore Courses';
        $c['specific_buttons']['BFKTm']['url'] = '/classes';
        $c['specific_buttons']['UsxeP']['title'] = 'Collaborate with Us';
        return $c;
    });

    /* ---------- Categories ---------- */
    $categories = [
        ['Food-Safety', 'Food Safety', 'Protect consumers with practical knowledge on food contaminants, hygiene and safe food handling', '/store/1/default_images/trend_categories_icons/health.svg', '#c6f1da'],
        ['Agripreneurship', 'Agripreneurship', 'Start, run and grow food and agribusiness ventures with practical business skills', '/store/1/default_images/trend_categories_icons/business.svg', '#ffe4b3'],
        ['Climate-and-Agriculture', 'Climate & Agriculture', 'Understand climate change impacts on agriculture and build resilient, sustainable farming systems', '/store/1/default_images/trend_categories_icons/science.svg', '#b3d5ff'],
        ['Aquaculture', 'Aquaculture & Circularity', 'Fish, spirulina and insect value chains, AI & IoT in aquaculture and circular food production', '/store/1/default_images/trend_categories_icons/lifestyle.svg', '#99dfff'],
    ];
    $trendIds = [];
    foreach ($categories as $i => [$slug, $title, $subtitle, $icon, $color]) {
        $catId = DB::table('categories')->where('slug', $slug)->value('id')
            ?? DB::table('categories')->insertGetId(['slug' => $slug, 'parent_id' => null, 'icon' => $icon, 'order' => $i + 1, 'enable' => 1]);
        DB::table('category_translations')->updateOrInsert(
            ['category_id' => $catId, 'locale' => 'en'],
            ['title' => $title, 'subtitle' => $subtitle, 'bottom_seo_title' => "About {$title} Courses", 'bottom_seo_content' => $subtitle . '. All CVH courses are free and include a certificate on completion.']
        );
        $trendIds[$slug] = DB::table('trend_categories')->where('category_id', $catId)->value('id')
            ?? DB::table('trend_categories')->insertGetId(['category_id' => $catId, 'icon' => $icon, 'color' => $color, 'created_at' => $now]);
    }

    /* ---------- Footer ---------- */
    updateHeaderFooter(2, function ($c) {
        $c['newsletter'] = array_merge($c['newsletter'], [
            'title' => 'Stay Updated with CVH',
            'subtitle' => 'Receive food system insights, new courses, live sessions and learning resources directly in your inbox',
            'button_text' => 'Join',
        ]);
        $c['cta'] = array_merge($c['cta'], [
            'pre_title' => 'Food System learning made easy',
            'title' => 'Join the Community Virtual Hub Today!',
            'button' => ['label' => 'Get Started', 'icon' => 'bul-teacher', 'url' => '/register'],
        ]);
        $c['contact'] = [
            'section_title' => 'Get in touch',
            'address' => 'Africa Alliance YMCA Building, Off State House Avenue, Behind Ethiopian Embassy, Nairobi',
            'phone' => '(+254) 748 464853',
            'email' => 'cvh@fspnafrica.org',
            'mobile' => '',
        ];
        $c['links_1_section_title'] = 'Important Links';
        $c['links_2_section_title'] = 'Course Categories';
        $c['copyright_text'] = '© 2025 cvh.africa. All rights reserved.';
        if (!hasCvhKeys($c['specific_links'] ?? null)) $c['specific_links'] = [
            'cvhF1' => ['title' => 'About us', 'url' => '/pages/about'],
            'cvhF2' => ['title' => 'Courses', 'url' => '/classes'],
            'cvhF3' => ['title' => 'Instructors', 'url' => '/instructors'],
            'cvhF4' => ['title' => 'Blog', 'url' => '/blog'],
            'cvhF5' => ['title' => 'Certificate Validation', 'url' => '/certificate_validation'],
            'cvhF6' => ['title' => 'Contact', 'url' => '/contact'],
            'cvhF7' => ['title' => 'Terms of use', 'url' => '/pages/terms'],
            'record' => ['title' => null, 'url' => null],
        ];
        $c['specific_links_2'] = [
            'cvhC1' => ['title' => 'Food Safety', 'url' => '/categories/Food-Safety'],
            'cvhC2' => ['title' => 'Agripreneurship', 'url' => '/categories/Agripreneurship'],
            'cvhC3' => ['title' => 'Climate & Agriculture', 'url' => '/categories/Climate-and-Agriculture'],
            'cvhC4' => ['title' => 'Aquaculture & Circularity', 'url' => '/categories/Aquaculture'],
            'record' => ['title' => null, 'url' => null],
        ];
        $c['social_media'] = [];   // no social accounts found on the source site
        return $c;
    });

    /* ---------- Homepage sections (landing 1) ---------- */
    updateLandingComponent(1, function ($c) {
        $c['upper_cta'] = ['badge_text' => 'Live', 'main_text' => 'Upcoming: HD4A Agroinnovators Mentorship Session 9', 'icon' => 'bol-video-circle', 'url' => str_starts_with($c['upper_cta']['url'] ?? '', '/bundles') ? $c['upper_cta']['url'] : '/classes'];
        $c['main_content']['title_line_1'] = 'Learn';
        $c['main_content']['title_line_2'] = "and shape Africa's food systems";
        $c['main_content']['description'] = "Learn about Africa's food systems, connect with peers and experts. Join the community learning hub to build a resilient food system.";
        $c['main_content']['primary_button'] = ['label' => 'Explore Courses', 'icon' => 'bul-video-horizontal', 'url' => '/classes'];
        $c['main_content']['secondary_button'] = ['label' => 'Meet Our Instructors', 'icon' => 'bul-teacher', 'url' => '/instructors'];
        $c['main_content']['highlight_words'] = ['cvhW1' => 'Food Safety', 'cvhW2' => 'Agribusiness', 'cvhW3' => 'Nutrition', 'cvhW4' => 'Aquaculture'];
        $c['students_widget'] = ['title' => 'Join 257+ learners in the CVH community', 'url' => '/register'];
        $c['image_content']['image'] = CVH . '/landing/hero_image.jpg';
        $c['image_content']['overlay_image'] = CVH . '/landing/hero_overlay.png';
        $c['image_content']['spinning_image'] = CVH . '/landing/hero_badge.png';
        $c['students_widget_avatars'] = [];
        foreach (range(1, 5) as $i) {
            $c['students_widget_avatars']["cvhv{$i}"] = CVH . "/landing/avatar_{$i}.png";
        }
        return $c;
    });

    updateLandingComponent(2, function ($c) {
        $c['statistics'] = [
            'cvhS1' => ['title' => 'Courses', 'icon' => 'bul-video-play', 'data_type' => 'manual', 'data_source' => 'number_of_courses', 'start_from' => null, 'manual_data' => '15+'],
            'cvhS2' => ['title' => 'Live Trainings Conducted', 'icon' => 'bul-briefcase', 'data_type' => 'manual', 'data_source' => 'number_of_live_courses', 'start_from' => null, 'manual_data' => '27'],
            'cvhS3' => ['title' => 'Enrolled Learners', 'icon' => 'bul-teacher', 'data_type' => 'manual', 'data_source' => 'number_of_students', 'start_from' => null, 'manual_data' => '257'],
            'cvhS4' => ['title' => 'Partner Organizations', 'icon' => 'bul-building', 'data_type' => 'manual', 'data_source' => 'number_of_organizations', 'start_from' => null, 'manual_data' => '41'],
            'record' => ['title' => null, 'icon' => null, 'data_type' => 'real', 'data_source' => 'number_of_instructors', 'start_from' => null, 'manual_data' => null],
        ];
        return $c;
    });

    updateLandingComponent(3, function ($c) {
        $c['main_content'] = ['pre_title' => 'Featured', 'title' => 'Featured Courses', 'description' => 'Courses developed in collaboration with experts from the world\'s leading agricultural, food and nutrition research organizations'];
        return $c;
    });

    updateLandingComponent(4, function ($c) use ($trendIds) {
        $c['main_content'] = ['pre_title' => 'Explore', 'title' => 'Explore Our Course Categories'];
        $c['trending_categories'] = ['record' => ['category' => null]];
        foreach (array_values($trendIds) as $i => $id) {
            $c['trending_categories']["cvhT{$i}"] = ['category' => (string)$id];
        }
        return $c;
    });

    updateLandingComponent(9, function ($c) {
        $c['main_content']['title'] = 'Top Rated Courses';
        $c['main_content']['subtitle'] = 'The courses our learners rate highest for practical, expert-led content across food safety, nutrition and agribusiness.';
        return $c;
    });

    updateLandingComponent(424, function ($c) {
        $c['main_content'] = ['title' => 'Free Courses', 'subtitle' => 'Every CVH course is free — learn at your own pace and earn a certificate on completion'];
        $c['cta_section'] = array_merge($c['cta_section'], [
            'title' => '100% free food system learning',
            'description' => 'No fees to learn and no fees for your certificate. Start your lifelong learning journey with CVH.',
            'link_title' => 'Explore Free Courses',
            'url' => '/classes?free=on',
        ]);
        return $c;
    });

    updateLandingComponent(12, function ($c) {
        $c['main_content'] = ['pre_title' => 'Instructors', 'title' => 'Meet Our Instructors & Mentors', 'description' => 'Learn from food system experts and mentors dedicated to practical, real-world guidance', 'button' => ['label' => 'All Instructors', 'icon' => 'bul-teacher', 'url' => '/instructors']];
        $c['cta_section'] = array_merge($c['cta_section'], [
            'title_bold_text' => 'Are you a food system expert?',
            'title_regular_text' => 'Collaborate with us to co-create impactful courses',
            'description' => 'Join our network of 41+ partners developing and reviewing CVH courses',
        ]);
        return $c;
    });

    updateLandingComponent(13, function ($c) {
        $c['main_content'] = ['pre_title' => 'Read More', 'title' => 'Latest News and Articles', 'description' => 'Food system insights, project news and stories from the innovators in our community', 'button' => ['label' => 'All Articles', 'icon' => 'bul-note-2', 'url' => '/blog']];
        return $c;
    });

    // Best-selling and discounted sections don't apply to an all-free catalogue: hide them and their spacers.
    foreach ([5, 423] as $componentId) {
        $order = DB::table('landing_components')->where('id', $componentId)->value('order');
        DB::table('landing_components')->where('landing_id', 1)->whereIn('order', [$order, $order + 1])->update(['enable' => 0]);
    }

    /* ---------- About page ---------- */
    $partners = [
        ['files/FSPN LOGO OFFICIAL.png', 'Food Security for Peace and Nutrition Africa (FSPN Africa)'],
        ['Alliance Bioversity International and CIAT.jpg', 'Alliance of Bioversity International and CIAT'],
        ['IITA.jpg', 'International Institute of Tropical Agriculture (IITA)'],
        ['africa-rice-center-organization-.png', 'AfricaRice'],
        ['files/icipe-logo.png', 'icipe'],
        ['KU Leuven.jpeg', 'KU Leuven'],
        ['Ghent_University_logo_-English-.png', 'Ghent University'],
        ['copanhagen-university.png', 'University of Copenhagen'],
        ['files/bonnlogo.jpg', 'University of Bonn'],
        ['download.png', 'Justus-Liebig-Universität Giessen'],
        ['Heidelberg Institute of Global Health- Heidelberg Universitätsklinikum.jpg', 'Heidelberg Institute of Global Health'],
        ['The Max Planck Institute of Molecular Plant Physiology.webp', 'Max Planck Institute of Molecular Plant Physiology'],
        ['The Center of Plant Systems Biology and Biotechnology.png', 'Center of Plant Systems Biology and Biotechnology'],
        ['De mont fort.png', 'De Montfort University'],
        ['files/Frau ISE.png', 'Fraunhofer ISE'],
        ['files/Joint Reseach.png', 'Joint Research Centre'],
        ['files/CIIMAR_logo_color - Ana Faria.png', 'CIIMAR'],
        ['kenyatta-university-logo-.png', 'Kenyatta University'],
        ['files/JKUAT-logo Tranparent BG.png', 'Jomo Kenyatta University of Agriculture and Technology'],
        ['files/maku logo png.png', 'MAKÜ'],
        ['files/makerere-university-logo.jpeg', 'Makerere University'],
        ['files/UDSM.jpeg', 'University of Dar es Salaam'],
        ['files/Unam.png', 'University of Namibia'],
        ['The University of Dschang.jpg', 'University of Dschang'],
        ['files/KMFRI.png', 'Kenya Marine and Fisheries Research Institute'],
        ['files/TAFIRI logo_tfr.png', 'Tanzania Fisheries Research Institute'],
        ['files/csir-logo_for_innoecofood - Kwabena Bugyei.png', 'CSIR Ghana'],
        ['Africa projects Centre.jpg', 'Africa Projects Centre'],
        ['Agribusiness Vocational Training Centre.jpeg', 'Agribusiness Vocational Training Centre'],
        ['CITA LOGO.png', 'CITA'],
        ['cerfam-logo-2.jpg', 'CERFAM'],
        ['Entreprise en Santé et Sécurité au Travail ESST.png', 'Entreprise en Santé et Sécurité au Travail (ESST)'],
        ['Opportunities Industrialization Center Côte d-Ivoire.jpg', 'Opportunities Industrialization Center Côte d\'Ivoire'],
        ['manobi_africa_.png', 'Manobi Africa'],
        ['files/CLAR.png', 'CLAR'],
        ['files/Convivencia_vertical_cores Adaptado - João Cota.png', 'Convivência'],
        ['files/INNOLOGO - Matthias Fischer.PNG', 'INNO'],
        ['files/LOGO - Redife Aslihan Ucar.png', 'Partner'],
        ['files/NK Sustainable Products Official Logo Transparent Background.png', 'NK Sustainable Products'],
        ['files/Peche Foods.jpg', 'Peche Foods'],
        ['files/Sparky Logo 2018 Final.png', 'Sparky Dryer'],
        ['files/logo vertical - cor - Ana Rita Ribeiro.jpg', 'Blue Bioeconomy CoLAB'],
    ];
    $u = fn($p) => CVH . '/' . implode('/', array_map('rawurlencode', explode('/', $p)));
    $logos = '';
    foreach ($partners as [$file, $name]) {
        $logos .= '<div style="width:140px;height:90px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid var(--gray-300);border-radius:12px;padding:10px;">'
            . '<img src="' . $u($file) . '" alt="' . e($name) . '" title="' . e($name) . '" style="max-width:100%;max-height:100%;object-fit:contain;" loading="lazy"></div>';
    }
    $feature = fn($icon, $title, $text) => '<div style="flex:1 1 220px;background:var(--gray-100);border-radius:16px;padding:20px;">'
        . ($icon ? '<img src="' . $u($icon) . '" alt="" style="width:64px;height:64px;object-fit:contain;margin-bottom:10px;background:#eaf3e7;border-radius:14px;padding:8px;">' : '')
        . '<h4 style="color:var(--secondary);margin:0 0 6px;">' . $title . '</h4><p style="margin:0;">' . $text . '</p></div>';
    $h2 = fn($t) => '<h2 style="color:var(--secondary);margin:32px 0 12px;">' . $t . '</h2>';
    $project = fn($logo, $title, $text) => '<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;margin-bottom:20px;">'
        . '<img src="' . $u($logo) . '" alt="' . e($title) . '" style="width:160px;max-width:100%;object-fit:contain;">'
        . '<div style="flex:1 1 300px;"><h4 style="color:var(--secondary);margin:0 0 6px;">' . $title . '</h4><p style="margin:0;">' . $text . '</p></div></div>';

    $html = '<h2 style="color:var(--secondary);margin:0 0 12px;">Creating change and positive impact through accessible and relevant digital learning</h2>'
        . '<p>CVH is providing you a digital learning experience focused on food system learning opportunities that address critical food and nutrition, climate change and agribusiness skills gaps in emerging markets.</p>'
        . $h2('About the Community Virtual Hub')
        . '<p>Community Virtual Hub is an e-learning platform funded by the European Union, and coordinated by Food Security for Peace and Nutrition Africa. It is Africa\'s best e-learning platform that offers a diverse range of free online courses tailored to all food system actors. We cater to individuals, as well as groups such as farmer organizations enrolling them in relevant courses.</p>'
        . '<p>Our platform provides a variety of free courses covering topics in Food systems (production, processing, distribution and marketing, nutrition &amp; consumption), Sustainability and Environmental Impact, Agricultural Technology and Innovation, Agricultural Finance and Investment, Regional Agricultural Policy, Community Engagement.</p>'
        . $h2('Why the Community Virtual Hub?')
        . '<div style="display:flex;flex-wrap:wrap;gap:16px;">'
        . $feature('files/1.png', 'Our Mission', 'To empower every actor with knowledge needed for a sustainable and resilient food system. Through these courses you gain the skills to improve livelihoods through sustainable and innovative food systems practices.')
        . $feature('files/2.png', 'Our Target Audience', 'The Community Virtual Hub courses target youth and women actors in the food system, from farmers to consumers. Are you a smallholder farmer in crop, livestock &amp; aquaculture sector, food vendor, processor, agribusiness startup or distributor? Our platform has tailored content to help you contribute to a resilient food system as you gain skills to maximize productivity and profitability.')
        . $feature('files/3.png', 'Primary Objective', 'Our primary objective is to empower all actors across the food system to collaboratively work towards creating a sustainable and resilient food system that aligns with achievement of Sustainable Development Goals.')
        . '</div>'
        . $h2('Why Join the CVH Community?')
        . '<div style="display:flex;flex-wrap:wrap;gap:16px;">'
        . $feature(null, 'Interactive Learning', 'Engage in dynamic, hands-on educational experiences.')
        . $feature(null, 'Expert Guidance', 'Benefit from insights and mentorship from industry experts.')
        . $feature(null, 'Career Advancement', 'Boost your career with targeted, practical skills.')
        . $feature(null, 'Community Support', 'Connect with a network of supportive, like-minded learners.')
        . '</div>'
        . $h2('Our Projects')
        . $project('files/HD4A logo.png', 'HealthyDiets4Africa Project', 'Healthydiets4Africa is an EU funded project focusing on the diversification of the food system to combat the triple burden of malnutrition while minimizing its environmental footprint in eight African countries. Capacity building and community empowerment is a key component of the project activities. Community Virtual Hub will host food safety and quality control courses tailored to all food value chain players in Kenya.')
        . $project('files/Innoecofood Logo Original.png', 'INNOECOFOOD Project', 'INNOECOFOOD Project is an EU funded project that aims to establish innovative production/business ECOHUBS, living lab cages and improve local aquaculture farms using AI and IoT in six African countries: Kenya, Uganda, Tanzania, Egypt, Namibia &amp; Ghana. One of the project objectives is to empower youth and women in the selected project areas to enhance sustainable development and employment. Youth and women will be trained on sustainable methods for production, processing, consumption &amp; marketing of fish, spirulina &amp; insects.')
        . $h2('Our Courses')
        . '<p><strong>Empowering you with Knowledge for a Sustainable Future.</strong> Our courses are connected to the following food system components: Sustainability and Environmental Impact, Regional Agricultural Policies, Agricultural Technology and Innovations, Agricultural Finance and Investment, Community Engagement, and Food Systems.</p>'
        . '<p>Courses are offered in a variety of formats, including videos, text-based modules, pictorials, interactive sessions with instructors, learner forums, scheduled webinars, and live classes led by our expert instructors. Each course is structured with modules and quizzes, and a digital certificate is awarded upon successful completion.</p>'
        . $h2('Who We Work With')
        . '<p>At the Community Virtual Hub, we collaborate with over 41 partners globally to develop, co-create, and review our courses, ensuring they are comprehensive, backed by expert knowledge and accredited courses. Our partners come from a diverse range of specialties across the food system, including research and development, community education and empowerment, livestock and fisheries, human nutrition, insect production, food system policies, crop cultivation, and food safety &amp; quality assurance. <strong>Are you a food system expert?</strong> <a href="/contact" style="color:var(--primary);">Collaborate with us</a> to co-create and deliver impactful learning experiences.</p>'
        . '<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:16px;">' . $logos . '</div>'
        . '<p style="margin-top:32px;"><a href="/classes" style="display:inline-block;background:var(--primary);color:#fff;padding:12px 28px;border-radius:12px;text-decoration:none;font-weight:600;">Enroll Now</a></p>';

    DB::table('pages')->where('id', 3)->update([
        'name' => 'About us', 'robot' => 1, 'status' => 'publish',
        'cover' => CVH . '/files/' . rawurlencode('photorealistic-view-woman-harvesting-organic-sustainable-garden.jpg'),
    ]);
    DB::table('page_translations')->updateOrInsert(['page_id' => 3, 'locale' => 'en'], [
        'title' => 'About the Community Virtual Hub',
        'subtitle' => 'Creating change and positive impact through accessible and relevant digital learning',
        'seo_description' => 'The Community Virtual Hub (CVH) is an EU-funded e-learning platform offering free food system courses across Africa.',
        'content' => $html,
    ]);
    // Reward points page is not part of CVH.
    DB::table('pages')->where('link', '/reward_points_system')->update(['status' => 'draft']);
});

echo "CVH phase 1 applied.\n";
