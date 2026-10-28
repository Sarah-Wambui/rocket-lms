<?php
/**
 * CVH Africa — add "Google Meet" to Settings → General → Features → Live Class Providers, so "Add New Live Session"
 * offers it, and clear the cached settings. Connecting the Google account does this automatically too.
 *
 *   php database/cvh/19_offer_google_meet.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo \App\Support\CvhGoogleMeet::offerInSessionForm()
    ? "Google Meet added to the live class providers.\n"
    : "Google Meet was already a live class provider (settings cache cleared).\n";
