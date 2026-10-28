<?php
/**
 * CVH Africa — import the old learn.cvh.africa site (an older Rocket LMS) from its database dump:
 * all user accounts (existing passwords keep working), the 10 active courses with their chapters, text lessons,
 * files and quizzes, plus learner history (enrolments, lesson progress, quiz results) and issued certificates.
 *
 *   php database/cvh/14_import_old_site.php /path/to/fspnafri_cvh_24.sql --dry-run     show what would happen, change nothing
 *   php database/cvh/14_import_old_site.php /path/to/fspnafri_cvh_24.sql [--store=/path/to/old/public/store]
 *   php database/cvh/14_import_old_site.php --undo                                      remove everything this script added
 *
 * - Reads the .sql file directly; no extra database is needed. Old IDs are remapped (table cvh_import_map), so
 *   re-running is safe: rows already imported are skipped.
 * - An account whose email already exists here is kept as it is (the old one is linked to it, not duplicated).
 *   Old instructors that exist here as placeholder accounts (@instructors.cvh.local, same name) get the real email,
 *   password and photo from the old site.
 * - Courses that already exist here (same title) keep their page; the old chapters/lessons/quizzes are added to them.
 * - Old files (/store/...) are referenced as /store/oldcvh/...; with --store they are copied from the old site's
 *   public/store folder. Files that could not be found are listed in storage/app/cvh_import_missing_files.txt.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

const MAP = 'cvh_import_map';
const STORE_PREFIX = '/store/oldcvh/';

// Old category id => our category id (Food Safety 612, Agripreneurship 613, Climate & Agriculture 614)
const CATEGORY_MAP = [
    523 => 614, // Sustainability & Environmental Impact
    524 => 614, // Agricultural Technology & Innovations
    525 => 613, // Agricultural Finance & Investments
    526 => 613, // Regional Agricultural Policies
    528 => 613, // Community Engagement
    606 => 613, // Community Training
    612 => 612, // Nutrition & Food Security
    615 => 613, // Agri-business Education
    616 => 614, // Food Systems
    617 => 613, // Jobs
];

$args = array_slice($argv, 1);
$undo = in_array('--undo', $args, true);
$dryRun = in_array('--dry-run', $args, true);
$storeDir = null;
$dumpPath = null;
foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) {
        $storeDir = rtrim(substr($arg, 8), '/\\');
    } elseif (!str_starts_with($arg, '--')) {
        $dumpPath = $arg;
    }
}

if ($undo) {
    undoImport();
    exit;
}
if (!$dumpPath || !is_file($dumpPath)) {
    exit("Usage: php database/cvh/14_import_old_site.php /path/to/old-site.sql [--store=/path/to/old/public/store] [--dry-run]\n"
        . "       php database/cvh/14_import_old_site.php --undo\n");
}
if ($storeDir && !is_dir($storeDir)) {
    exit("The --store folder does not exist: {$storeDir}\n");
}

/* ======================================================================================================== */

/** Rows of the given tables from a phpMyAdmin/mysqldump file: [table => [[column => value], ...]]. */
function readDump(string $path, array $tables): array
{
    $sql = file_get_contents($path);
    $result = array_fill_keys($tables, []);
    preg_match_all('/INSERT INTO `(\w+)` \(([^)]*)\) VALUES\s*/', $sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    foreach ($matches as $match) {
        $table = $match[1][0];
        if (!array_key_exists($table, $result)) {
            continue;
        }
        $columns = array_map(fn($c) => trim($c, " `"), explode(',', $match[2][0]));
        $i = $match[0][1] + strlen($match[0][0]);

        while (true) {
            while (strpos(" \r\n\t,", $sql[$i]) !== false) $i++;
            if ($sql[$i] === ';') break;
            if ($sql[$i] !== '(') throw new RuntimeException("Unexpected dump format in {$table} near: " . substr($sql, $i, 60));
            $i++;
            $values = [];
            while (true) {
                while ($sql[$i] === ' ') $i++;
                if ($sql[$i] === "'") {
                    $i++;
                    $buffer = '';
                    while (true) {
                        $next = strcspn($sql, "\\'", $i);
                        $buffer .= substr($sql, $i, $next);
                        $i += $next;
                        $char = $sql[$i];
                        if ($char === '\\') {
                            $escaped = $sql[$i + 1];
                            $buffer .= ['n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1a"][$escaped] ?? $escaped;
                            $i += 2;
                        } elseif ($sql[$i + 1] === "'") {
                            $buffer .= "'";
                            $i += 2;
                        } else {
                            $i++;
                            break;
                        }
                    }
                    $values[] = $buffer;
                } else {
                    $next = strcspn($sql, ',)', $i);
                    $token = trim(substr($sql, $i, $next));
                    $i += $next;
                    if (strtoupper($token) === 'NULL') {
                        $values[] = null;
                    } elseif (preg_match("/^(0x[0-9a-f]+|b'[01]+')$/i", $token)) {
                        $values[] = DB::raw($token); // binary literals (geometry `location`, bit fields): pass through as-is
                    } else {
                        $values[] = $token;
                    }
                }
                while ($sql[$i] === ' ') $i++;
                if ($sql[$i] === ',') { $i++; continue; }
                if ($sql[$i] === ')') { $i++; break; }
            }
            $result[$table][] = array_combine($columns, $values);
        }
    }

    return $result;
}

function ensureMapTable(): void
{
    if (!Schema::hasTable(MAP)) {
        Schema::create(MAP, function (Blueprint $table) {
            $table->increments('id');
            $table->string('source', 64);
            $table->unsignedBigInteger('old_id');
            $table->unsignedBigInteger('new_id');
            $table->boolean('created')->default(true); // false = linked to a row that already existed here
            $table->text('prev')->nullable();            // values changed on an existing row (restored by --undo)
            $table->unique(['source', 'old_id']);
        });
    }
}

/** New id for an old id, or null when it was not imported. */
function mapped(string $source, $oldId): ?int
{
    static $cache = [];
    if ($oldId === null || $oldId === '') return null;
    if (!isset($cache[$source])) {
        $cache[$source] = DB::table(MAP)->where('source', $source)->pluck('new_id', 'old_id')->all();
    }
    if (func_num_args() === 3) { // internal: remember a new mapping
        $cache[$source][$oldId] = func_get_arg(2);
        return null;
    }
    return isset($cache[$source][$oldId]) ? (int)$cache[$source][$oldId] : null;
}

function remember(string $source, $oldId, int $newId, bool $created, ?array $prev = null): void
{
    DB::table(MAP)->insert(['source' => $source, 'old_id' => $oldId, 'new_id' => $newId, 'created' => $created, 'prev' => $prev ? json_encode($prev) : null]);
    mapped($source, $oldId, $newId);
}

function columns(string $table): array
{
    static $cache = [];
    return $cache[$table] ??= array_flip(Schema::getColumnListing($table));
}

$stats = [];
function tally(string $key, int $by = 1): void
{
    global $stats;
    $stats[$key] = ($stats[$key] ?? 0) + $by;
}

/** Insert an old row (only columns that exist here) with overrides; returns the new id. Skips rows already imported. */
function put(string $table, array $row, array $overrides = []): int
{
    if ($existing = mapped($table, $row['id'])) {
        tally("{$table} (already imported)");
        return $existing;
    }
    $data = array_intersect_key(array_merge($row, $overrides), columns($table));
    unset($data['id']);
    $newId = DB::table($table)->insertGetId($data);
    remember($table, $row['id'], $newId, true);
    tally($table);
    return $newId;
}

/** Old "/store/..." path -> "/store/oldcvh/...", copying the file from the old store folder when available. */
function oldFile(?string $path): ?string
{
    global $storeDir, $dryRun, $missingFiles;
    if ($path === null || $path === '') return $path;
    $path = preg_replace('#^https?://(www\.)?learn\.cvh\.africa(?=/store/)#i', '', $path);
    if (!str_starts_with($path, '/store/')) return $path;

    $relative = substr($path, strlen('/store/'));
    $newPath = STORE_PREFIX . $relative;
    $target = public_path(ltrim($newPath, '/'));
    if (!is_file($target)) {
        $source = $storeDir ? $storeDir . '/' . $relative : null;
        if ($source && is_file($source)) {
            if (!$dryRun) {
                @mkdir(dirname($target), 0775, true);
                copy($source, $target);
            }
            tally('files copied');
        } else {
            $missingFiles[$path] = true;
        }
    }
    return $newPath;
}

/** Rewrite /store/... links inside lesson HTML. */
function oldFilesInHtml(?string $html): ?string
{
    if ($html === null) return null;
    return preg_replace_callback('#((?:src|href)\s*=\s*["\'])((?:https?://(?:www\.)?learn\.cvh\.africa)?/store/[^"\']+)#i',
        fn($m) => $m[1] . oldFile(html_entity_decode($m[2])), $html);
}

/* ======================================================================================================== */

$tables = ['users', 'users_metas', 'users_occupations', 'webinars', 'webinar_translations', 'webinar_partner_teacher',
    'tags', 'faqs', 'faq_translations', 'webinar_extra_descriptions', 'webinar_extra_description_translations', 'webinar_reviews',
    'webinar_chapters', 'webinar_chapter_translations', 'text_lessons', 'text_lesson_translations', 'files', 'file_translations',
    'quizzes', 'quiz_translations', 'quizzes_questions', 'quiz_question_translations', 'quizzes_questions_answers',
    'quizzes_questions_answer_translations', 'webinar_chapter_items', 'sales', 'course_learning', 'quizzes_results',
    'certificates', 'certificates_templates', 'certificate_template_translations'];

echo "Reading " . basename($dumpPath) . " ...\n";
$old = readDump($dumpPath, $tables);
foreach (['users', 'webinars'] as $required) {
    if (empty($old[$required])) exit("No `{$required}` rows found in the dump; is this the right file?\n");
}
$missingFiles = [];
ensureMapTable();
$byId = fn(string $table) => array_column($old[$table], null, 'id');
$translationsOf = function (string $table, string $fk) use (&$old) {
    $grouped = [];
    foreach ($old[$table] as $row) $grouped[$row[$fk]][] = $row;
    return $grouped;
};

DB::beginTransaction();
try {
    /* ---------- 1. Users ---------- */
    $roles = DB::table('roles')->pluck('id', 'name')->all();
    $emails = DB::table('users')->whereNotNull('email')->pluck('id', DB::raw('LOWER(email)'))->all();
    $placeholderTeachers = DB::table('users')->where('email', 'like', '%@instructors.cvh.local')->pluck('id', 'full_name')->all();

    foreach ($old['users'] as $user) {
        if (mapped('users', $user['id'])) { tally('users (already imported)'); continue; }
        $email = $user['email'] !== null ? strtolower(trim($user['email'])) : null;

        if ($email && isset($emails[$email])) {
            remember('users', $user['id'], $emails[$email], false);
            tally('users linked to an existing account (same email)');
            continue;
        }

        if ($user['role_name'] === 'teacher' && isset($placeholderTeachers[$user['full_name']])) {
            $ourId = $placeholderTeachers[$user['full_name']];
            $current = (array)DB::table('users')->where('id', $ourId)->first();
            $changes = ['email' => $user['email'], 'password' => $user['password']];
            foreach (['avatar' => oldFile($user['avatar']), 'bio' => $user['bio'], 'about' => $user['about'], 'headline' => $user['headline']] as $field => $value) {
                if ($value && empty($current[$field])) $changes[$field] = $value;
            }
            if ($user['mobile'] && empty($current['mobile']) && !DB::table('users')->where('mobile', $user['mobile'])->exists()) {
                $changes['mobile'] = $user['mobile'];
            }
            DB::table('users')->where('id', $ourId)->update($changes);
            remember('users', $user['id'], $ourId, false, array_intersect_key($current, $changes));
            $emails[$email] = $ourId;
            tally('users merged into placeholder instructor');
            continue;
        }

        $mobile = $user['mobile'];
        if ($mobile && DB::table('users')->where('mobile', $mobile)->exists()) $mobile = null;
        $newId = put('users', $user, [
            'role_id' => $roles[$user['role_name']] ?? $roles['user'],
            'organ_id' => null, // set below, once every account exists
            'mobile' => $mobile,
            'avatar' => oldFile($user['avatar']),
            'cover_img' => oldFile($user['cover_img']),
            'identity_scan' => oldFile($user['identity_scan']),
            'certificate' => oldFile($user['certificate']),
        ]);
        if ($email) $emails[$email] = $newId;
    }
    foreach ($old['users'] as $user) {
        if ($user['organ_id'] && ($organId = mapped('users', $user['organ_id']))) {
            DB::table('users')->where('id', mapped('users', $user['id']))->whereNull('organ_id')->update(['organ_id' => $organId]);
        }
    }
    $createdUser = fn($oldId) => DB::table(MAP)->where(['source' => 'users', 'old_id' => $oldId, 'created' => true])->exists();
    foreach ($old['users_metas'] as $meta) {
        if ($createdUser($meta['user_id'])) put('users_metas', $meta, ['user_id' => mapped('users', $meta['user_id'])]);
    }
    foreach ($old['users_occupations'] as $occupation) {
        $category = CATEGORY_MAP[(int)$occupation['category_id']] ?? null;
        if ($category && $createdUser($occupation['user_id'])) {
            put('users_occupations', $occupation, ['user_id' => mapped('users', $occupation['user_id']), 'category_id' => $category]);
        }
    }
    $userOr = fn($oldId, $fallback = 1) => mapped('users', $oldId) ?? $fallback;

    /* ---------- 2. Courses ---------- */
    $webinarTitles = array_column(array_filter($old['webinar_translations'], fn($t) => $t['locale'] === 'en'), 'title', 'webinar_id');
    $ourTitles = DB::table('webinar_translations')->join('webinars', 'webinars.id', '=', 'webinar_translations.webinar_id')
        ->whereNull('webinars.deleted_at')->where('locale', 'en')
        ->pluck('webinars.id', DB::raw('LOWER(TRIM(title))'))->all();
    $webinarTranslations = $translationsOf('webinar_translations', 'webinar_id');

    foreach ($old['webinars'] as $course) {
        if ($course['status'] !== 'active' || $course['deleted_at']) continue;
        if (mapped('webinars', $course['id'])) { tally('webinars (already imported)'); continue; }
        $title = $webinarTitles[$course['id']] ?? $course['slug'];
        $teacherId = $userOr($course['teacher_id']);

        // Same title, or ours is the old title without its subtitle ("Climate Change and Agriculture: A Two Way Impact")
        $key = strtolower(trim($title));
        $shortKey = strtolower(trim(explode(':', $title)[0]));
        if (isset($ourTitles[$key]) || isset($ourTitles[$shortKey])) {
            $ourId = $ourTitles[$key] ?? $ourTitles[$shortKey];
            $current = (array)DB::table('webinars')->where('id', $ourId)->first();
            $changes = ['teacher_id' => $teacherId, 'certificate' => max((int)$current['certificate'], (int)$course['certificate'])];
            DB::table('webinars')->where('id', $ourId)->update($changes);
            remember('webinars', $course['id'], $ourId, false, array_intersect_key($current, $changes));
            tally('webinars merged into an existing course (same title)');
            continue;
        }

        $slug = Str::slug($course['slug']);
        while (DB::table('webinars')->where('slug', $slug)->exists()) $slug .= '-2';
        $newId = put('webinars', $course, [
            'slug' => $slug,
            'teacher_id' => $teacherId,
            'creator_id' => $userOr($course['creator_id'], $teacherId),
            'category_id' => CATEGORY_MAP[(int)$course['category_id']] ?? 614,
            'thumbnail' => oldFile($course['thumbnail']),
            'image_cover' => oldFile($course['image_cover']),
            'video_demo' => $course['video_demo_source'] === 'upload' ? oldFile($course['video_demo']) : $course['video_demo'],
            'forum' => 1, // course Q&A, as on the other CVH courses
        ]);
        foreach ($webinarTranslations[$course['id']] ?? [] as $translation) {
            put('webinar_translations', $translation, ['webinar_id' => $newId, 'description' => oldFilesInHtml($translation['description'])]);
        }
    }

    $createdCourse = fn($oldId) => DB::table(MAP)->where(['source' => 'webinars', 'old_id' => $oldId, 'created' => true])->exists();
    foreach ($old['webinar_partner_teacher'] as $row) {
        if (($webinarId = mapped('webinars', $row['webinar_id'])) && ($teacher = mapped('users', $row['teacher_id']))
            && !DB::table('webinar_partner_teacher')->where(['webinar_id' => $webinarId, 'teacher_id' => $teacher])->exists()) {
            put('webinar_partner_teacher', $row, ['webinar_id' => $webinarId, 'teacher_id' => $teacher]);
        }
    }
    foreach ($old['tags'] as $row) {
        if ($row['webinar_id'] && $createdCourse($row['webinar_id'])) put('tags', $row, ['webinar_id' => mapped('webinars', $row['webinar_id']), 'bundle_id' => null, 'upcoming_course_id' => null]);
    }
    $faqTranslations = $translationsOf('faq_translations', 'faq_id');
    foreach ($old['faqs'] as $row) {
        if (!$row['webinar_id'] || !$createdCourse($row['webinar_id'])) continue;
        $newId = put('faqs', $row, ['webinar_id' => mapped('webinars', $row['webinar_id']), 'creator_id' => $userOr($row['creator_id']), 'bundle_id' => null, 'upcoming_course_id' => null]);
        foreach ($faqTranslations[$row['id']] ?? [] as $t) put('faq_translations', $t, ['faq_id' => $newId]);
    }
    $extraTranslations = $translationsOf('webinar_extra_description_translations', 'webinar_extra_description_id');
    foreach ($old['webinar_extra_descriptions'] as $row) {
        if (!$row['webinar_id'] || !$createdCourse($row['webinar_id'])) continue;
        $newId = put('webinar_extra_descriptions', $row, ['webinar_id' => mapped('webinars', $row['webinar_id']), 'creator_id' => $userOr($row['creator_id']), 'upcoming_course_id' => null]);
        foreach ($extraTranslations[$row['id']] ?? [] as $t) put('webinar_extra_description_translations', $t, ['webinar_extra_description_id' => $newId]);
    }
    foreach ($old['webinar_reviews'] as $row) {
        if (($webinarId = mapped('webinars', $row['webinar_id'])) && ($creator = mapped('users', $row['creator_id']))) {
            put('webinar_reviews', $row, ['webinar_id' => $webinarId, 'creator_id' => $creator, 'bundle_id' => null]);
        }
    }

    /* ---------- 3. Course content ---------- */
    $chapterTranslations = $translationsOf('webinar_chapter_translations', 'webinar_chapter_id');
    foreach ($old['webinar_chapters'] as $chapter) {
        if (!($webinarId = mapped('webinars', $chapter['webinar_id']))) continue;
        $newId = put('webinar_chapters', $chapter, ['webinar_id' => $webinarId, 'user_id' => $userOr($chapter['user_id'])]);
        foreach ($chapterTranslations[$chapter['id']] ?? [] as $t) put('webinar_chapter_translations', $t, ['webinar_chapter_id' => $newId]);
    }

    $lessonTranslations = $translationsOf('text_lesson_translations', 'text_lesson_id');
    foreach ($old['text_lessons'] as $lesson) {
        if (!($webinarId = mapped('webinars', $lesson['webinar_id']))) continue;
        $newId = put('text_lessons', $lesson, [
            'webinar_id' => $webinarId, 'chapter_id' => mapped('webinar_chapters', $lesson['chapter_id']),
            'creator_id' => $userOr($lesson['creator_id']), 'image' => oldFile($lesson['image']),
        ]);
        foreach ($lessonTranslations[$lesson['id']] ?? [] as $t) {
            put('text_lesson_translations', $t, ['text_lesson_id' => $newId, 'content' => oldFilesInHtml($t['content'])]);
        }
    }

    $fileTranslations = $translationsOf('file_translations', 'file_id');
    foreach ($old['files'] as $file) {
        if (!($webinarId = mapped('webinars', $file['webinar_id']))) continue;
        $newId = put('files', $file, [
            'webinar_id' => $webinarId, 'chapter_id' => mapped('webinar_chapters', $file['chapter_id']),
            'creator_id' => $userOr($file['creator_id']), 'file' => $file['storage'] === 'upload' ? oldFile($file['file']) : $file['file'],
        ]);
        foreach ($fileTranslations[$file['id']] ?? [] as $t) put('file_translations', $t, ['file_id' => $newId]);
    }

    $quizTranslations = $translationsOf('quiz_translations', 'quiz_id');
    foreach ($old['quizzes'] as $quiz) {
        if (!($webinarId = mapped('webinars', $quiz['webinar_id']))) continue;
        $newId = put('quizzes', $quiz, ['webinar_id' => $webinarId, 'chapter_id' => mapped('webinar_chapters', $quiz['chapter_id']), 'creator_id' => $userOr($quiz['creator_id'])]);
        foreach ($quizTranslations[$quiz['id']] ?? [] as $t) put('quiz_translations', $t, ['quiz_id' => $newId]);
    }
    $questionTranslations = $translationsOf('quiz_question_translations', 'quizzes_question_id');
    foreach ($old['quizzes_questions'] as $question) {
        if (!($quizId = mapped('quizzes', $question['quiz_id']))) continue;
        $newId = put('quizzes_questions', $question, [
            'quiz_id' => $quizId, 'creator_id' => $userOr($question['creator_id']),
            'image' => oldFile($question['image']), 'video' => oldFile($question['video']),
        ]);
        foreach ($questionTranslations[$question['id']] ?? [] as $t) put('quiz_question_translations', $t, ['quizzes_question_id' => $newId]);
    }
    $answerTranslations = $translationsOf('quizzes_questions_answer_translations', 'quizzes_questions_answer_id');
    foreach ($old['quizzes_questions_answers'] as $answer) {
        if (!($questionId = mapped('quizzes_questions', $answer['question_id']))) continue;
        $newId = put('quizzes_questions_answers', $answer, ['question_id' => $questionId, 'creator_id' => $userOr($answer['creator_id']), 'image' => oldFile($answer['image'])]);
        foreach ($answerTranslations[$answer['id']] ?? [] as $t) put('quizzes_questions_answer_translations', $t, ['quizzes_questions_answer_id' => $newId]);
    }

    $itemSource = ['text_lesson' => 'text_lessons', 'quiz' => 'quizzes', 'file' => 'files'];
    foreach ($old['webinar_chapter_items'] as $item) {
        $chapterId = mapped('webinar_chapters', $item['chapter_id']);
        $itemId = isset($itemSource[$item['type']]) ? mapped($itemSource[$item['type']], $item['item_id']) : null;
        if ($chapterId && $itemId) put('webinar_chapter_items', $item, ['chapter_id' => $chapterId, 'item_id' => $itemId, 'user_id' => $userOr($item['user_id'])]);
    }

    /* ---------- 4. Learner history ---------- */
    foreach ($old['sales'] as $sale) {
        if ($sale['type'] !== 'webinar') continue;
        $webinarId = mapped('webinars', $sale['webinar_id']);
        $buyerId = mapped('users', $sale['buyer_id']);
        if (!$webinarId || !$buyerId) continue;
        if (!mapped('sales', $sale['id']) && DB::table('sales')->where(['buyer_id' => $buyerId, 'webinar_id' => $webinarId])->whereNull('refund_at')->exists()) {
            tally('sales (learner already enrolled here)');
            continue;
        }
        $nulls = array_fill_keys(['order_id', 'bundle_id', 'event_ticket_id', 'meeting_id', 'meeting_time_id', 'meeting_package_id', 'subscribe_id',
            'ticket_id', 'promotion_id', 'product_order_id', 'registration_package_id', 'installment_payment_id', 'gift_id'], null);
        put('sales', $sale, ['webinar_id' => $webinarId, 'buyer_id' => $buyerId, 'seller_id' => $userOr($sale['seller_id'])] + $nulls);
    }

    foreach ($old['course_learning'] as $row) {
        $userId = mapped('users', $row['user_id']);
        $refs = ['text_lesson_id' => mapped('text_lessons', $row['text_lesson_id']), 'file_id' => mapped('files', $row['file_id']), 'session_id' => null];
        if ($userId && ($refs['text_lesson_id'] || $refs['file_id'])) put('course_learning', $row, ['user_id' => $userId] + $refs);
    }

    foreach ($old['quizzes_results'] as $row) {
        $quizId = mapped('quizzes', $row['quiz_id']);
        $userId = mapped('users', $row['user_id']);
        if (!$quizId || !$userId) continue;
        $results = json_decode((string)$row['results'], true);
        if (is_array($results)) {
            $remapped = [];
            foreach ($results as $questionId => $value) {
                if (is_numeric($questionId) && ($newQuestion = mapped('quizzes_questions', $questionId))) {
                    if (is_array($value) && isset($value['answer']) && is_numeric($value['answer'])) {
                        $value['answer'] = mapped('quizzes_questions_answers', $value['answer']) ?? $value['answer'];
                    }
                    $remapped[$newQuestion] = $value;
                } else {
                    $remapped[$questionId] = $value;
                }
            }
            $row['results'] = json_encode($remapped);
        }
        put('quizzes_results', $row, ['quiz_id' => $quizId, 'user_id' => $userId]);
    }

    /* ---------- 5. Issued certificates ---------- */
    $hasCvhCertificates = Schema::hasTable('cvh_certificates');
    foreach ($old['certificates'] as $row) {
        if ($row['type'] !== 'course') continue;
        $webinarId = mapped('webinars', $row['webinar_id']);
        $userId = mapped('users', $row['student_id']);
        if (!$webinarId || !$userId) continue;
        put('certificates', $row, ['webinar_id' => $webinarId, 'student_id' => $userId, 'quiz_id' => null, 'quiz_result_id' => null, 'bundle_id' => null]);

        $studentName = trim((string)DB::table('users')->where('id', $userId)->value('full_name'));
        if ($studentName === '') {
            // Issued when the learner adds their name and downloads it (the imported `certificates` row proves they earned it)
            tally('cvh_certificates (waiting for the learner to add their name)');
            continue;
        }
        if ($hasCvhCertificates && !mapped('cvh_certificates', $row['id'])
            && !DB::table('cvh_certificates')->where(['user_id' => $userId, 'webinar_id' => $webinarId])
                ->when(Schema::hasColumn('cvh_certificates', 'quiz_id'), fn($q) => $q->where('quiz_id', 0))->exists()) {
            do {
                $code = 'CVH-' . date('Y', (int)$row['created_at']) . '-' . strtoupper(Str::random(8));
            } while (DB::table('cvh_certificates')->where('code', $code)->exists());
            $newId = DB::table('cvh_certificates')->insertGetId([
                'code' => $code, 'user_id' => $userId, 'webinar_id' => $webinarId,
                'student_name' => $studentName,
                'course_title' => DB::table('webinar_translations')->where(['webinar_id' => $webinarId, 'locale' => 'en'])->value('title'),
                'issued_at' => (int)$row['created_at'],
            ]);
            remember('cvh_certificates', $row['id'], $newId, true);
            tally('cvh_certificates');
        }
    }

    /* ---------- 6. Certificate design: copy the old template's background image ---------- */
    $templateTitles = array_column($old['certificate_template_translations'], 'title', 'certificate_template_id');
    foreach ($old['certificates_templates'] as $template) {
        if ($template['type'] === 'course' && $template['status'] === 'publish') {
            echo "Old certificate template: \"" . ($templateTitles[$template['id']] ?? $template['id']) . "\" -> " . oldFile($template['image']) . "\n";
        }
    }

    if ($dryRun) {
        DB::rollBack();
    } else {
        DB::commit();
    }
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

/* ---------- Report ---------- */
echo $dryRun ? "\nDRY RUN — nothing was changed. This is what would be imported:\n" : "\nImported:\n";
ksort($stats);
foreach ($stats as $key => $count) printf("  %-55s %d\n", $key, $count);

if ($missingFiles) {
    $list = storage_path('app/cvh_import_missing_files.txt');
    file_put_contents($list, implode("\n", array_keys($missingFiles)) . "\n");
    echo "\n" . count($missingFiles) . " old files (photos, course covers, certificate background) are not here yet; they are listed in\n  {$list}\n"
        . "Copy them by running this again with --store=/path/to/the/old/site/public/store\n";
}
if (!$dryRun) echo "\nUndo with: php database/cvh/14_import_old_site.php --undo\n";

/* ======================================================================================================== */

function undoImport(): void
{
    if (!Schema::hasTable(MAP)) exit("Nothing to undo (no import found).\n");

    // Children before parents
    $order = ['cvh_certificates', 'certificates', 'quizzes_results', 'course_learning', 'sales', 'webinar_chapter_items',
        'quizzes_questions_answer_translations', 'quizzes_questions_answers', 'quiz_question_translations', 'quizzes_questions',
        'quiz_translations', 'quizzes', 'file_translations', 'files', 'text_lesson_translations', 'text_lessons',
        'webinar_chapter_translations', 'webinar_chapters', 'webinar_reviews', 'webinar_extra_description_translations',
        'webinar_extra_descriptions', 'faq_translations', 'faqs', 'tags', 'webinar_partner_teacher', 'webinar_translations',
        'webinars', 'users_occupations', 'users_metas', 'users'];

    DB::transaction(function () use ($order) {
        foreach ($order as $table) {
            $rows = DB::table(MAP)->where('source', $table)->get();
            $created = $rows->where('created', true)->pluck('new_id')->all();
            foreach (array_chunk($created, 500) as $chunk) DB::table($table)->whereIn('id', $chunk)->delete();
            foreach ($rows->where('created', false)->whereNotNull('prev') as $row) {
                DB::table($table)->where('id', $row->new_id)->update(json_decode($row->prev, true));
            }
            if ($rows->count()) printf("  %-45s %d removed%s\n", $table, count($created), $rows->where('created', false)->count() ? ', ' . $rows->where('created', false)->whereNotNull('prev')->count() . ' restored' : '');
        }
    });
    Schema::drop(MAP);
    echo "Import undone. (Copied files in public" . STORE_PREFIX . " were left in place.)\n";
}
