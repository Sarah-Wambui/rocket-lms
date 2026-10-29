<?php
/**
 * CVH Africa — fill courses with their content from the old TrainerCentral academy (learn.cvh.africa / admin.cvh.africa).
 *
 *   php database/cvh/23_import_trainercentral.php <export-dir>             show what would be imported
 *   php database/cvh/23_import_trainercentral.php <export-dir> --apply     import it
 *   php database/cvh/23_import_trainercentral.php --undo                   remove everything this script imported
 *
 * <export-dir> is the folder the TrainerCentral export made: all_courses.json (courses, sections, lessons),
 * materials.json (each lesson's text / files / recordings), files/docs/<materialId>-<name> (the downloaded PDFs and
 * documents), and optionally tests.json (the tests, see trainercentral_tests format below) and recordings.json
 * ({"<recordingId>": "<YouTube or other video link>"}).
 *
 * A TrainerCentral course goes into the LMS course with the same slug, and only when that course has no content yet
 * (so the courses migrated from the old Rocket site are never doubled). Sections become chapters; a lesson's rich text
 * becomes a text lesson, its PDFs/documents downloadable files (copied to public/store/trainercentral/<slug>/), its
 * recording a video lesson when recordings.json has a link for it, and its test a quiz. Every row made is recorded in
 * cvh_import_map (source "tc:<table>") so --undo removes exactly that.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

const TEXT = '10', PRESENTATION = '5', DOCUMENT = '11', RECORDING = '8';

if (!Schema::hasTable('cvh_import_map')) {
    Schema::create('cvh_import_map', function ($t) {
        $t->increments('id');
        $t->string('source', 64);
        $t->bigInteger('old_id');
        $t->bigInteger('new_id');
        $t->boolean('created')->default(1);
        $t->text('prev')->nullable();
        $t->unique(['source', 'old_id']);
    });
}

/* ---------- undo ---------- */
if (in_array('--undo', $argv, true)) {
    $order = ['quizzes_questions_answer_translations', 'quizzes_questions_answers', 'quiz_question_translations', 'quizzes_questions',
        'quiz_translations', 'quizzes', 'webinar_chapter_items', 'file_translations', 'files', 'text_lesson_translations', 'text_lessons',
        'webinar_chapter_translations', 'webinar_chapters'];
    $total = 0;
    foreach ($order as $table) {
        $ids = DB::table('cvh_import_map')->where('source', "tc:{$table}")->pluck('new_id');
        $total += DB::table($table)->whereIn('id', $ids)->delete();
        DB::table('cvh_import_map')->where('source', "tc:{$table}")->delete();
    }
    $copied = public_path('store/trainercentral');
    if (is_dir($copied)) Illuminate\Support\Facades\File::deleteDirectory($copied);
    Cache::flush();
    exit("Removed {$total} imported rows and the copied files.\n");
}

$dir = rtrim($argv[1] ?? '', '/\\');
if (!$dir || !is_file("{$dir}/all_courses.json") || !is_file("{$dir}/materials.json")) {
    exit("Usage: php database/cvh/23_import_trainercentral.php <export-dir> [--apply]   (the folder with all_courses.json and materials.json)\n");
}
$apply = in_array('--apply', $argv, true);
$courses = json_decode(file_get_contents("{$dir}/all_courses.json"), true);
$materials = json_decode(file_get_contents("{$dir}/materials.json"), true);
$tests = is_file("{$dir}/tests.json") ? json_decode(file_get_contents("{$dir}/tests.json"), true) : [];
$recordings = is_file("{$dir}/recordings.json") ? json_decode(file_get_contents("{$dir}/recordings.json"), true) : [];
$docs = [];
foreach (glob("{$dir}/files/docs/*") ?: [] as $path) {
    if (preg_match('/^(\d+)-/', basename($path), $m)) $docs[$m[1]] = $path;
}

$now = time();
$put = function (string $table, array $row, $tcId = null) use ($apply): int {
    if (!$apply) return 0;
    $id = DB::table($table)->insertGetId($row);
    DB::table('cvh_import_map')->insert(['source' => "tc:{$table}", 'old_id' => $tcId ?? $id, 'new_id' => $id, 'created' => 1]);
    return $id;
};
$hasContent = fn($webinarId) => DB::table('text_lessons')->where('webinar_id', $webinarId)->exists()
    || DB::table('files')->where('webinar_id', $webinarId)->exists()
    || DB::table('quizzes')->where('webinar_id', $webinarId)->exists()
    || DB::table('webinar_chapters')->where('webinar_id', $webinarId)->exists();

// TrainerCentral's editor wraps everything in inline font styles; keep the text, alignment, lists, links and images
$cleanHtml = function (?string $html): string {
    $html = (string)$html;
    $html = preg_replace_callback('/\sstyle="([^"]*)"/i', function ($m) {
        return preg_match('/text-align:\s*(center|right|justify)/i', html_entity_decode($m[1]), $a) ? " style=\"text-align: {$a[1]}\"" : '';
    }, $html);
    $html = preg_replace('/\sclass="(ui-rte[^"]*|rte-[^"]*)"/i', '', $html);
    return trim($html);
};
$fileType = function (string $path): array {
    $head = (string)file_get_contents($path, false, null, 0, 8);
    if (str_starts_with($head, '%PDF')) return ['pdf', 'pdf'];
    if (str_starts_with($head, "PK")) {
        $zip = new ZipArchive();
        if ($zip->open($path) === true) {
            $kind = $zip->locateName('word/document.xml') !== false ? 'docx' : ($zip->locateName('ppt/presentation.xml') !== false ? 'pptx' : ($zip->locateName('xl/workbook.xml') !== false ? 'xlsx' : 'zip'));
            $zip->close();
            return [$kind, $kind === 'zip' ? 'archive' : 'document'];
        }
    }
    if (str_starts_with($head, "\xD0\xCF\x11\xE0")) return ['doc', 'document'];
    return ['bin', 'document'];
};

$summary = [];
foreach ($courses as $c) {
    $course = $c['course'];
    $slug = $course['uniqueKey'] ?? '';
    $webinar = DB::table('webinars')->where('slug', $slug)->whereNull('deleted_at')->first();
    $title = $course['courseName'] ?? $slug;
    if (!$webinar) { $summary[] = ['skip', $title, 'not in the LMS']; continue; }
    if ($hasContent($webinar->id)) { $summary[] = ['skip', $title, 'already has content']; continue; }

    $teacher = $webinar->teacher_id ?: $webinar->creator_id;
    $sections = collect($c['sections']['sections'] ?? [])->sortBy(fn($s) => (int)$s['sectionIndex'])->values();
    $lessons = collect($c['sessionInfos']['sessions'] ?? [])->groupBy('section');
    $counts = ['chapters' => 0, 'text' => 0, 'files' => 0, 'videos' => 0, 'quizzes' => 0, 'questions' => 0, 'missing' => 0];

    foreach ($sections as $sIndex => $section) {
        $chapterId = $put('webinar_chapters', ['user_id' => $teacher, 'webinar_id' => $webinar->id, 'order' => $sIndex + 1,
            'check_all_contents_pass' => 0, 'status' => 'active', 'created_at' => $now], $section['id']);
        if ($apply) DB::table('webinar_chapter_translations')->insert(['webinar_chapter_id' => $chapterId, 'locale' => 'en', 'title' => Str::limit($section['name'], 250, '')]);
        $counts['chapters']++;
        $order = 0;
        $addItem = function (int $itemId, string $type) use (&$order, $put, $chapterId, $teacher, $now) {
            $put('webinar_chapter_items', ['user_id' => $teacher, 'chapter_id' => $chapterId, 'item_id' => $itemId, 'type' => $type, 'order' => ++$order, 'created_at' => $now]);
        };

        foreach (collect($lessons[$section['id']] ?? [])->sortBy(fn($l) => (int)$l['sessionIndex']) as $lesson) {
            $sm = collect($materials[$lesson['id']]['sessionMaterials'] ?? [])->sortBy(fn($x) => (int)($x['sessionMaterialIndex'] ?? 0));
            $byId = collect($materials[$lesson['id']]['materials'] ?? [])->keyBy('materialId');
            $items = $sm->map(fn($x) => $byId[$x['materialId'] ?? ''] ?? null)->filter()->values();
            if ($items->isEmpty() && $byId->isNotEmpty()) $items = $byId->values();

            foreach ($items as $m) {
                $name = trim($m['name'] ?? '') ?: $lesson['name'];
                if ($m['materialType'] === TEXT) {
                    $content = $cleanHtml($m['materialText'] ?? '');
                    if (!strip_tags($content)) continue;
                    $id = $put('text_lessons', ['creator_id' => $teacher, 'webinar_id' => $webinar->id, 'chapter_id' => $chapterId, 'image' => null,
                        'study_time' => max(1, (int)ceil(str_word_count(strip_tags($content)) / 200)), 'accessibility' => 'paid',
                        'check_previous_parts' => 0, 'order' => $order + 1, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], $m['materialId']);
                    if ($apply) {
                        DB::table('text_lesson_translations')->insert(['text_lesson_id' => $id, 'locale' => 'en', 'title' => Str::limit($name, 250, ''),
                            'summary' => Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($content)))), 250), 'content' => $content]);
                        $addItem($id, 'text_lesson');
                    }
                    $counts['text']++;
                } elseif (in_array($m['materialType'], [PRESENTATION, DOCUMENT], true)) {
                    $src = $docs[$m['materialId']] ?? null;
                    if (!$src) { $counts['missing']++; continue; }
                    [$ext, $kind] = $fileType($src);
                    $rel = "/store/trainercentral/{$webinar->slug}/" . Str::slug(pathinfo($name, PATHINFO_FILENAME)) . "-{$m['materialId']}.{$ext}";
                    if ($apply) {
                        @mkdir(dirname(public_path($rel)), 0755, true);
                        copy($src, public_path($rel));
                    }
                    $id = $put('files', ['creator_id' => $teacher, 'webinar_id' => $webinar->id, 'chapter_id' => $chapterId, 'accessibility' => 'paid',
                        'downloadable' => 1, 'storage' => 'upload', 'file' => $rel, 'volume' => round(filesize($src) / 1048576, 2), 'file_type' => $ext,
                        'check_previous_parts' => 0, 'online_viewer' => $ext === 'pdf' ? 1 : 0, 'order' => $order + 1, 'status' => 'active', 'created_at' => $now], $m['materialId']);
                    if ($apply) {
                        DB::table('file_translations')->insert(['file_id' => $id, 'locale' => 'en', 'title' => Str::limit($name, 250, ''), 'description' => $lesson['name']]);
                        $addItem($id, 'file');
                    }
                    $counts['files']++;
                } elseif ($m['materialType'] === RECORDING) {
                    $link = $recordings[$m['recordingId'] ?? $m['materialId']] ?? null;
                    if (!$link) { $counts['missing']++; continue; }
                    $storage = preg_match('/youtu\.?be/i', $link) ? 'youtube' : (preg_match('/vimeo/i', $link) ? 'vimeo' : 'external_link');
                    $id = $put('files', ['creator_id' => $teacher, 'webinar_id' => $webinar->id, 'chapter_id' => $chapterId, 'accessibility' => 'paid',
                        'downloadable' => 0, 'storage' => $storage, 'file' => $link, 'volume' => 0, 'file_type' => 'video',
                        'check_previous_parts' => 0, 'online_viewer' => 0, 'order' => $order + 1, 'status' => 'active', 'created_at' => $now], $m['materialId']);
                    if ($apply) {
                        DB::table('file_translations')->insert(['file_id' => $id, 'locale' => 'en', 'title' => Str::limit($lesson['name'] . ' (recording)', 250, ''), 'description' => $lesson['name']]);
                        $addItem($id, 'file');
                    }
                    $counts['videos']++;
                }
            }

            // trainercentral_tests format: [{sessionId, name, passPercent, questions: [{text, type: multiple|descriptive, grade, options: [{text, correct}]}]}]
            foreach (collect($tests)->where('sessionId', $lesson['id']) as $test) {
                $questions = $test['questions'] ?? [];
                if (!$questions) continue;
                $total = array_sum(array_map(fn($q) => (int)($q['grade'] ?? 1), $questions));
                $quizId = $put('quizzes', ['webinar_id' => $webinar->id, 'creator_id' => $teacher, 'chapter_id' => $chapterId, 'time' => 0, 'attempt' => 3,
                    'pass_mark' => (int)round($total * (($test['passPercent'] ?? 70) / 100)), 'certificate' => 1, 'status' => 'active', 'total_mark' => $total,
                    'display_limited_questions' => 0, 'display_questions_randomly' => 0, 'created_at' => $now, 'updated_at' => $now], null);
                if ($apply) {
                    DB::table('quiz_translations')->insert(['quiz_id' => $quizId, 'locale' => 'en', 'title' => Str::limit($test['name'] ?: "{$lesson['name']} test", 250, '')]);
                    $addItem($quizId, 'quiz');
                }
                foreach ($questions as $qIndex => $q) {
                    $multiple = ($q['type'] ?? 'multiple') === 'multiple' && !empty($q['options']);
                    $qId = $put('quizzes_questions', ['quiz_id' => $quizId, 'creator_id' => $teacher, 'grade' => (string)($q['grade'] ?? 1), 'type' => $multiple ? 'multiple' : 'descriptive',
                        'order' => $qIndex + 1, 'created_at' => $now, 'updated_at' => $now], null);
                    if ($apply) DB::table('quiz_question_translations')->insert(['quizzes_question_id' => $qId, 'locale' => 'en', 'title' => trim(strip_tags($q['text'] ?? '')), 'correct' => $multiple ? null : ($q['answer'] ?? null)]);
                    foreach ($multiple ? $q['options'] : [] as $opt) {
                        $aId = $put('quizzes_questions_answers', ['creator_id' => $teacher, 'question_id' => $qId, 'correct' => !empty($opt['correct']) ? 1 : 0, 'created_at' => $now, 'updated_at' => $now], null);
                        if ($apply) DB::table('quizzes_questions_answer_translations')->insert(['quizzes_questions_answer_id' => $aId, 'locale' => 'en', 'title' => trim(strip_tags($opt['text'] ?? ''))]);
                    }
                    $counts['questions']++;
                }
                $counts['quizzes']++;
            }
        }
    }
    $summary[] = ['fill', $title, $counts];
}

foreach ($summary as [$action, $title, $info]) {
    printf("%-5s %-70s %s\n", strtoupper($action), Str::limit($title, 68), is_array($info)
        ? "{$info['chapters']} sections, {$info['text']} text, {$info['files']} files, {$info['videos']} videos, {$info['quizzes']} quizzes ({$info['questions']} questions)" . ($info['missing'] ? ", {$info['missing']} not available (file or video link missing)" : '')
        : $info);
}
if ($apply) {
    Cache::flush();
    echo "\nImported. Undo: php database/cvh/23_import_trainercentral.php --undo\n";
} else {
    echo "\nNothing changed. Run again with --apply to import.\n";
}
