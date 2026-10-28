<?php
/**
 * CVH Africa — let cvh_certificates hold quiz certificates as well as course certificates.
 * Adds quiz_id (0 = course certificate), quiz_title and grade, and makes a certificate unique per
 * learner + course + quiz. Safe to run more than once.
 *
 *   php database/cvh/17_quiz_certificates.php
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_certificates')) {
    exit("Run database/cvh/11_certificates.php first.\n");
}

if (!Schema::hasColumn('cvh_certificates', 'quiz_id')) {
    Schema::table('cvh_certificates', function (Blueprint $table) {
        $table->unsignedInteger('quiz_id')->default(0)->after('webinar_id');
        $table->string('quiz_title', 255)->nullable()->after('course_title');
        $table->string('grade', 32)->nullable()->after('quiz_title');
    });
    echo "Added quiz columns.\n";
}

$indexes = collect(DB::select('SHOW INDEX FROM cvh_certificates'))->pluck('Key_name')->unique();

if (!$indexes->contains('cvh_certificates_user_course_quiz_unique')) {
    Schema::table('cvh_certificates', function (Blueprint $table) {
        $table->unique(['user_id', 'webinar_id', 'quiz_id'], 'cvh_certificates_user_course_quiz_unique');
    });
}
if ($indexes->contains('cvh_certificates_user_id_webinar_id_unique')) {
    Schema::table('cvh_certificates', function (Blueprint $table) {
        $table->dropUnique('cvh_certificates_user_id_webinar_id_unique');
    });
}

echo "cvh_certificates is ready for quiz certificates.\n";
