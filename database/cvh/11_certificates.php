<?php
/**
 * CVH Africa — in-house certificate of completion.
 *  - creates the cvh_certificates table (if missing)
 *  - footer: "Certificate Validation" -> /certificates/verify, adds "My Certificates" -> /my-certificates
 * Run from the project root:  php database/cvh/11_certificates.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_certificates')) {
    Schema::create('cvh_certificates', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code', 32)->unique();
        $table->unsignedInteger('user_id')->index();
        $table->unsignedInteger('webinar_id')->index();
        $table->string('student_name');
        $table->string('course_title');
        $table->unsignedInteger('issued_at');
        $table->unique(['user_id', 'webinar_id']);
    });
    echo "Created table cvh_certificates.\n";
}

DB::transaction(function () {
    $footer = DB::table('theme_header_footer_translations')->where('theme_header_footer_id', 2)->where('locale', 'en')->first();
    $c = json_decode($footer->content, true);
    $links = $c['specific_links'] ?? [];
    $out = [];
    foreach ($links as $k => $l) {
        if ($k === 'record') continue;
        if (is_array($l) && ($l['url'] ?? null) === '/certificate_validation') {
            $l['url'] = '/certificates/verify';
        }
        if (is_array($l) && ($l['url'] ?? null) === '/my-certificates') continue; // re-added below
        $out[$k] = $l;
        if (is_array($l) && ($l['url'] ?? null) === '/certificates/verify') {
            $out['cvhMyCerts'] = ['title' => 'My Certificates', 'url' => '/my-certificates'];
        }
    }
    if (!isset($out['cvhMyCerts'])) $out['cvhMyCerts'] = ['title' => 'My Certificates', 'url' => '/my-certificates'];
    $out['record'] = $links['record'] ?? ['title' => null, 'url' => null];
    $c['specific_links'] = $out;
    DB::table('theme_header_footer_translations')->where('id', $footer->id)->update(['content' => json_encode($c)]);
});

echo "Certificates ready (footer links updated).\n";
