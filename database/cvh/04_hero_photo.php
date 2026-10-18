<?php
/**
 * CVH Africa — client feedback: use CVH's own hero photo (woman with tablet in a melon greenhouse,
 * from learn.cvh.africa) instead of the previous image.
 * Run from the project root:  php database/cvh/04_hero_photo.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('landing_component_translations')->where('landing_component_id', 1)->where('locale', 'en')->first();
$content = json_decode($row->content, true);
$content['image_content']['image'] = '/store/cvh/landing/hero_image_v2.jpg';
DB::table('landing_component_translations')->where('id', $row->id)->update(['content' => json_encode($content)]);

echo "Hero photo updated.\n";
