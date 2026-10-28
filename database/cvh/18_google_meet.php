<?php
/**
 * CVH Africa — database bits for the Google Meet integration (App\Support\CvhGoogleMeet):
 * sessions.google_event_id (the Google Calendar event behind a session's Meet) and the cvh_settings table.
 * Safe to run more than once.
 *
 *   php database/cvh/18_google_meet.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_settings')) {
    Schema::create('cvh_settings', function (Blueprint $table) {
        $table->string('name', 64)->primary();
        $table->longText('value')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
    });
    echo "Created cvh_settings.\n";
}

if (!Schema::hasColumn('sessions', 'google_event_id')) {
    Schema::table('sessions', function (Blueprint $table) {
        $table->string('google_event_id', 255)->nullable()->after('zoom_id');
    });
    echo "Added sessions.google_event_id.\n";
}

echo "Google Meet is ready to connect: Admin → Settings → Google Meet.\n";
