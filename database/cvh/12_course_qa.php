<?php
/**
 * CVH Africa — in-house course Q&A (replaces the paid "Course Forum" plugin feature).
 * Creates cvh_course_questions and cvh_course_answers (if missing) and turns the Q&A option on for CVH courses.
 * Run from the project root:  php database/cvh/12_course_qa.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('cvh_course_questions')) {
    Schema::create('cvh_course_questions', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('webinar_id')->index();
        $table->unsignedInteger('user_id')->index();
        $table->string('title');
        $table->text('body');
        $table->boolean('is_pinned')->default(false);
        $table->unsignedInteger('resolved_answer_id')->nullable();
        $table->unsignedInteger('answers_count')->default(0);
        $table->unsignedInteger('last_answer_at')->nullable();
        $table->unsignedInteger('created_at')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
        $table->unsignedInteger('deleted_at')->nullable();
    });
    echo "Created table cvh_course_questions.\n";
}

if (!Schema::hasTable('cvh_course_answers')) {
    Schema::create('cvh_course_answers', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('question_id')->index();
        $table->unsignedInteger('user_id')->index();
        $table->text('body');
        $table->boolean('is_pinned')->default(false);
        $table->unsignedInteger('created_at')->nullable();
        $table->unsignedInteger('updated_at')->nullable();
        $table->unsignedInteger('deleted_at')->nullable();
    });
    echo "Created table cvh_course_answers.\n";
}

// The course "Forum" option controls whether Q&A shows for a course; turn it on for the CVH courses.
$cvhSlugs = DB::table('webinars')->where('status', 'active')->where('thumbnail', 'like', '/store/cvh/%')->pluck('slug');
DB::table('webinars')->whereIn('slug', $cvhSlugs)->update(['forum' => 1]);

echo 'Course Q&A ready (enabled on ' . count($cvhSlugs) . " CVH courses).\n";
