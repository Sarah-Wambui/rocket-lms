<?php
/**
 * CVH Africa — a small PRIVATE test course with two PDF lessons, for trying the full learner flow
 * (enrol -> complete lessons -> download certificate). Private = hidden from course lists; only the
 * enrolled tester (and admins) can open it.
 *
 *   php database/cvh/13_certificate_test_course.php learner@example.com   create (if needed) + enrol that account
 *   php database/cvh/13_certificate_test_course.php --remove               delete the test course and everything about it
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

const SLUG = 'cvh-certificate-test-course';
const LESSON_DIR = 'store/cvh/test-course';

$arg = $argv[1] ?? null;
if (!$arg) {
    exit("Usage: php database/cvh/13_certificate_test_course.php learner@example.com   |   --remove\n");
}

/* ---------- --remove ---------- */
if ($arg === '--remove') {
    $course = DB::table('webinars')->where('slug', SLUG)->first();
    if (!$course) exit("Test course not found; nothing to remove.\n");

    DB::transaction(function () use ($course) {
        $fileIds = DB::table('files')->where('webinar_id', $course->id)->pluck('id');
        $chapterIds = DB::table('webinar_chapters')->where('webinar_id', $course->id)->pluck('id');
        DB::table('course_learning')->whereIn('file_id', $fileIds)->delete();
        DB::table('file_translations')->whereIn('file_id', $fileIds)->delete();
        DB::table('webinar_chapter_items')->whereIn('chapter_id', $chapterIds)->delete();
        DB::table('files')->whereIn('id', $fileIds)->delete();
        DB::table('webinar_chapter_translations')->whereIn('webinar_chapter_id', $chapterIds)->delete();
        DB::table('webinar_chapters')->whereIn('id', $chapterIds)->delete();
        DB::table('sales')->where('webinar_id', $course->id)->delete();
        DB::table('certificates')->where('webinar_id', $course->id)->delete();
        if (DB::getSchemaBuilder()->hasTable('cvh_certificates')) DB::table('cvh_certificates')->where('webinar_id', $course->id)->delete();
        if (DB::getSchemaBuilder()->hasTable('cvh_course_questions')) {
            $qids = DB::table('cvh_course_questions')->where('webinar_id', $course->id)->pluck('id');
            DB::table('cvh_course_answers')->whereIn('question_id', $qids)->delete();
            DB::table('cvh_course_questions')->whereIn('id', $qids)->delete();
        }
        DB::table('webinar_translations')->where('webinar_id', $course->id)->delete();
        DB::table('webinars')->where('id', $course->id)->delete();
    });
    foreach (glob(public_path(LESSON_DIR . '/*.pdf')) as $f) @unlink($f);
    exit("Test course removed.\n");
}

/* ---------- create + enrol ---------- */
$learner = DB::table('users')->where('email', $arg)->first();
if (!$learner) exit("No account with the email {$arg}. Register it on the site first, then run this again.\n");

$now = time();
$lessons = [
    ['Welcome to the Community Virtual Hub', 'lesson-1.pdf', [
        'This is a short test lesson for checking the learning flow and certificate of completion on CVH Africa.',
        'Open each lesson in the course, then tick "I passed this lesson" next to its title.',
        'When every lesson is ticked, your progress reaches 100% and the certificate unlocks.',
    ]],
    ['How your certificate works', 'lesson-2.pdf', [
        'At 100% progress a green "Course completed!" box appears on the learning page with a "Get your certificate" button.',
        'Your certificates are also listed under Dashboard > Certificates > My Certificates.',
        'Each certificate has a unique ID and QR code that anyone can check at /certificates/verify.',
    ]],
];

// Generate the PDF lessons
@mkdir(public_path(LESSON_DIR), 0775, true);
foreach ($lessons as $i => [$title, $file, $paragraphs]) {
    $html = '<html><body style="font-family: DejaVu Sans, sans-serif; padding: 40px; color: #121f3e;">'
        . '<p style="color:#5eac49; font-size: 12pt; letter-spacing: 2pt;">CVH AFRICA — TEST LESSON ' . ($i + 1) . '</p>'
        . '<h1 style="color:#0e2145;">' . e($title) . '</h1>'
        . implode('', array_map(fn($p) => '<p style="font-size: 13pt; line-height: 1.6;">' . e($p) . '</p>', $paragraphs))
        . '</body></html>';
    Pdf::loadHTML($html)->setPaper('a4')->save(public_path(LESSON_DIR . '/' . $file));
}

DB::transaction(function () use ($learner, $now, $lessons) {
    $category = DB::table('categories')->where('slug', 'Food-Safety')->value('id');

    $courseId = DB::table('webinars')->where('slug', SLUG)->value('id');
    $courseData = [
        'teacher_id' => 1, 'creator_id' => 1, 'category_id' => $category, 'type' => 'course', 'private' => 1,
        'thumbnail' => '/store/cvh/courses/acrylamide.jpg', 'image_cover' => '/store/cvh/courses/acrylamide_cover.jpg',
        'duration' => 10, 'price' => null, 'support' => 1, 'certificate' => 1, 'downloadable' => 1, 'forum' => 1,
        'timezone' => 'Africa/Nairobi', 'status' => 'active', 'updated_at' => $now, 'deleted_at' => null,
    ];
    if ($courseId) {
        DB::table('webinars')->where('id', $courseId)->update($courseData);
    } else {
        $courseId = DB::table('webinars')->insertGetId(['slug' => SLUG, 'created_at' => $now] + $courseData);
    }
    DB::table('webinar_translations')->updateOrInsert(['webinar_id' => $courseId, 'locale' => 'en'], [
        'title' => 'CVH Certificate Test Course',
        'summary' => 'A short private test course for trying lessons, progress and the certificate of completion.',
        'seo_description' => 'CVH certificate test course',
        'description' => '<p>A short private test course with two PDF lessons. Complete both to unlock your certificate.</p>',
    ]);

    // One chapter with two PDF lessons (recreated each run)
    $chapterId = DB::table('webinar_chapters')->where('webinar_id', $courseId)->value('id');
    if (!$chapterId) {
        $chapterId = DB::table('webinar_chapters')->insertGetId(['user_id' => 1, 'webinar_id' => $courseId, 'order' => 1, 'status' => 'active', 'check_all_contents_pass' => 0, 'created_at' => $now]);
        DB::table('webinar_chapter_translations')->insert(['webinar_chapter_id' => $chapterId, 'locale' => 'en', 'title' => 'Introduction']);
    }
    if (!DB::table('files')->where('webinar_id', $courseId)->exists()) {
        foreach ($lessons as $i => [$title, $file]) {
            $path = public_path(LESSON_DIR . '/' . $file);
            $fileId = DB::table('files')->insertGetId([
                'creator_id' => 1, 'webinar_id' => $courseId, 'chapter_id' => $chapterId, 'accessibility' => 'free',
                'downloadable' => 1, 'storage' => 'upload', 'file' => '/' . LESSON_DIR . '/' . $file,
                'volume' => round(filesize($path) / 1048576, 2) . ' MB', 'file_type' => 'pdf', 'online_viewer' => 1,
                'order' => $i + 1, 'status' => 'active', 'created_at' => $now,
            ]);
            DB::table('file_translations')->insert(['file_id' => $fileId, 'locale' => 'en', 'title' => $title, 'description' => 'Test lesson ' . ($i + 1)]);
            DB::table('webinar_chapter_items')->insert(['user_id' => 1, 'chapter_id' => $chapterId, 'item_id' => $fileId, 'type' => 'file', 'order' => $i + 1, 'created_at' => $now]);
        }
    }

    // Enrol the tester (free, manual)
    if (!DB::table('sales')->where('buyer_id', $learner->id)->where('webinar_id', $courseId)->whereNull('refund_at')->exists()) {
        DB::table('sales')->insert([
            'buyer_id' => $learner->id, 'seller_id' => 1, 'webinar_id' => $courseId, 'type' => 'webinar', 'payment_method' => 'credit',
            'amount' => 0, 'total_amount' => 0, 'manual_added' => 1, 'access_to_purchased_item' => 1, 'created_at' => $now,
        ]);
    }
});

$url = rtrim(config('app.url'), '/') . '/course/learning/' . SLUG;
echo "Test course ready and {$learner->email} is enrolled.\nLog in as that account and open: {$url}\nRemove it later with: php database/cvh/13_certificate_test_course.php --remove\n";
