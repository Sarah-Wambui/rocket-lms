<?php
/**
 * CVH Africa — custom Innovations showcase (/innovations), built in-house instead of the paid Store plugin.
 *  - creates the cvh_innovators table (if missing) and loads all HD4A innovators from innovators.json
 *  - full profiles for the three "Innovator of the Month" businesses come from their blog posts
 *  - points the menu and footer "Innovations" links to /innovations
 * Run from the project root:  php database/cvh/08_innovations_showcase.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

if (!Schema::hasTable('cvh_innovators')) {
    Schema::create('cvh_innovators', function (Blueprint $table) {
        $table->increments('id');
        $table->string('slug')->unique();
        $table->string('name');
        $table->string('business');
        $table->string('location')->nullable();
        $table->string('country', 64)->nullable();
        $table->unsignedTinyInteger('cohort');
        $table->boolean('is_winner')->default(false);
        $table->string('photo')->nullable();
        $table->text('summary')->nullable();
        $table->longText('profile_html')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('enable')->default(true);
        $table->unsignedInteger('created_at')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
    });
    echo "Created table cvh_innovators.\n";
}

$winners = ['Lilian Bitok', 'Judy Mwende', 'Mary Karoki', 'Benson Kagwima', 'Boaz Tuju', 'Mary Ngugi', 'Albanus Mwangangi', 'Mercy Manyange'];
$profilePosts = [
    'Benson Kagwima' => 'innovator-of-the-month-ryans-food-kenya',
    'Boaz Tuju' => 'innovator-of-the-month-asili-farm-africa',
    'Mary Ngugi' => 'innovator-of-the-month-momix-enterprise-crunchy-roots',
];

$innovators = json_decode(file_get_contents(__DIR__ . '/innovators.json'), true);
$now = time();

DB::transaction(function () use ($innovators, $winners, $profilePosts, $now) {
    foreach ($innovators as $i => $x) {
        preg_match('/Cohort\s+(\d)/', $x['cohort'], $m);
        $cohort = (int)($m[1] ?? 0);
        $isWinner = in_array($x['name'], $winners, true);
        $parts = array_map('trim', explode(',', $x['location']));
        $country = end($parts);
        $slug = Str::slug($x['business'] . ' ' . $x['name']);

        $summary = $x['name'] . ' leads ' . $x['business'] . ' in ' . $x['location'] . ', a ' . ($isWinner ? 'winner' : 'finalist')
            . ' in Cohort ' . $cohort . ' of the HealthyDiets4Africa Agribusiness Accelerator Program.';
        $profileHtml = null;

        if (isset($profilePosts[$x['name']])) {
            $post = DB::table('blog')->join('blog_translations as t', 't.blog_id', '=', 'blog.id')
                ->where('blog.slug', $profilePosts[$x['name']])->where('t.locale', 'en')
                ->select('t.description', 't.content')->first();
            if ($post) {
                $summary = $post->description;
                $profileHtml = $post->content;
            }
        }

        DB::table('cvh_innovators')->updateOrInsert(['slug' => $slug], [
            'name' => $x['name'],
            'business' => $x['business'],
            'location' => $x['location'],
            'country' => $country,
            'cohort' => $cohort,
            'is_winner' => $isWinner,
            'photo' => '/store/cvh/people/' . Str::slug(str_replace('.', '', $x['name'])) . '.jpg',
            'summary' => $summary,
            'profile_html' => $profileHtml,
            'sort_order' => $i + 1,
            'enable' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // Menu + footer: "Innovations" now goes to the showcase.
    $relink = function (array $links, string $urlKey) {
        foreach ($links as $k => $l) {
            if (is_array($l) && ($l[$urlKey] ?? null) === '/pages/innovations') {
                $links[$k][$urlKey] = '/innovations';
            }
        }
        return $links;
    };
    foreach ([1, 2] as $headerFooterId) {
        $row = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', $headerFooterId)->where('locale', 'en')->first();
        $c = json_decode($row->content, true);
        $c['specific_links'] = $relink($c['specific_links'] ?? [], 'url');
        DB::table('theme_header_footer_translations')->where('id', $row->id)->update(['content' => json_encode($c)]);
    }
    $nav = DB::table('setting_translations')->where('setting_id', DB::table('settings')->where('name', 'navbar_links')->value('id'))->where('locale', 'en')->first();
    DB::table('setting_translations')->where('id', $nav->id)->update(['value' => json_encode($relink(json_decode($nav->value, true) ?: [], 'link'))]);
});

echo 'Innovations showcase ready: ' . DB::table('cvh_innovators')->count() . " innovators.\n";
