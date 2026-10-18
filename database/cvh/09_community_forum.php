<?php
/**
 * CVH Africa — in-house community forum at /community (our own code and tables; the template's
 * paid Forum plugin at /forums stays untouched).
 *  - creates cvh_forum_categories, cvh_forum_topics, cvh_forum_replies (if missing)
 *  - adds the CVH discussion categories and a pinned welcome topic
 *  - points the menu "Forums" link to /community
 * Run from the project root:  php database/cvh/09_community_forum.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_forum_categories')) {
    Schema::create('cvh_forum_categories', function (Blueprint $table) {
        $table->increments('id');
        $table->string('slug')->unique();
        $table->string('title');
        $table->string('description', 500)->nullable();
        $table->string('icon', 64)->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('enable')->default(true);
        $table->unsignedInteger('created_at')->nullable();
    });
}

if (!Schema::hasTable('cvh_forum_topics')) {
    Schema::create('cvh_forum_topics', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('category_id')->index();
        $table->unsignedInteger('user_id')->index();
        $table->string('title');
        $table->text('body');
        $table->boolean('is_pinned')->default(false);
        $table->boolean('is_locked')->default(false);
        $table->unsignedInteger('views')->default(0);
        $table->unsignedInteger('replies_count')->default(0);
        $table->unsignedInteger('last_reply_at')->nullable();
        $table->unsignedInteger('last_reply_user_id')->nullable();
        $table->unsignedInteger('created_at')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
        $table->unsignedInteger('deleted_at')->nullable();
    });
}

if (!Schema::hasTable('cvh_forum_replies')) {
    Schema::create('cvh_forum_replies', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('topic_id')->index();
        $table->unsignedInteger('user_id')->index();
        $table->text('body');
        $table->unsignedInteger('created_at')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
        $table->unsignedInteger('deleted_at')->nullable();
    });
}

$now = time();

DB::transaction(function () use ($now) {
    $categories = [
        ['food-safety', 'Food Safety', 'Food contaminants, hygiene, safe handling and quality control across the value chain.', 'bul-shield-tick'],
        ['agribusiness', 'Agribusiness & Entrepreneurship', 'Starting, running and scaling food and agribusiness ventures: finance, markets and operations.', 'bul-briefcase'],
        ['climate-agriculture', 'Climate & Agriculture', 'Climate-smart farming, resilience, sustainability and environmental impact.', 'bul-cloud-sunny'],
        ['aquaculture', 'Aquaculture & Circularity', 'Fish, spirulina and insect value chains, AI & IoT in aquaculture and circular production (INNOECOFOOD).', 'bul-drop'],
        ['innovators-corner', "HD4A Innovators' Corner", 'For the HealthyDiets4Africa agro-innovators: share progress, ask for advice and find partners.', 'bul-lamp-on'],
        ['general', 'General Discussion', 'Introductions, questions about the platform and everything else food systems.', 'bul-messages-2'],
    ];
    foreach ($categories as $i => [$slug, $title, $description, $icon]) {
        DB::table('cvh_forum_categories')->updateOrInsert(['slug' => $slug], [
            'title' => $title, 'description' => $description, 'icon' => $icon, 'sort_order' => $i + 1, 'enable' => 1, 'created_at' => $now,
        ]);
    }

    $generalId = DB::table('cvh_forum_categories')->where('slug', 'general')->value('id');
    $welcomeTitle = 'Welcome to the CVH Community!';
    if (!DB::table('cvh_forum_topics')->where('title', $welcomeTitle)->exists()) {
        DB::table('cvh_forum_topics')->insert([
            'category_id' => $generalId, 'user_id' => 1, 'title' => $welcomeTitle,
            'body' => "Welcome to the Community Virtual Hub forum!\n\nThis is the place to connect with fellow learners, instructors and food system experts across Africa. Ask questions about your courses, share what you are working on, and learn from each other.\n\nA few simple rules:\n- Be respectful and constructive.\n- Stay on topic and post in the right category.\n- No spam or advertising.\n\nStart by introducing yourself below: who you are, where you are based and what part of the food system you work in.",
            'is_pinned' => 1, 'is_locked' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // Menu: "Forums" -> /community (header links + legacy navbar_links setting)
    $relink = function (array $links, string $urlKey) {
        foreach ($links as $k => $l) {
            if (is_array($l) && ($l[$urlKey] ?? null) === '/forums') {
                $links[$k][$urlKey] = '/community';
            }
        }
        return $links;
    };
    $header = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 1)->where('locale', 'en')->first();
    $c = json_decode($header->content, true);
    $c['specific_links'] = $relink($c['specific_links'] ?? [], 'url');
    DB::table('theme_header_footer_translations')->where('id', $header->id)->update(['content' => json_encode($c)]);
    $nav = DB::table('setting_translations')->where('setting_id', DB::table('settings')->where('name', 'navbar_links')->value('id'))->where('locale', 'en')->first();
    DB::table('setting_translations')->where('id', $nav->id)->update(['value' => json_encode($relink(json_decode($nav->value, true) ?: [], 'link'))]);
});

echo 'Community forum ready: ' . DB::table('cvh_forum_categories')->count() . " categories.\n";
