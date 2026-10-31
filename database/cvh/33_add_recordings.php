<?php
/**
 * CVH Africa — add the TrainerCentral class recordings to the courses imported before they were downloaded
 * (23_import_trainercentral.php skips a course that already has content). Each recording becomes a video lesson in
 * its section, where it was on TrainerCentral: just before the slides and notes of its lesson. Lessons that were free
 * previews on TrainerCentral become free previews here (their video, text and files), as the import made them paid.
 *
 *   php database/cvh/33_add_recordings.php <export-dir>            show what would be added
 *   php database/cvh/33_add_recordings.php <export-dir> --apply    add them
 *   php database/cvh/33_add_recordings.php --undo                  remove the videos it added, put the previews back
 *
 * Recordings are read like the importer reads them: <export-dir>/files/recordings/<recordingId>.mp4 (served from this
 * server; 31_sort_recordings.ps1 names them so) or <export-dir>/recordings.json ({"<recordingId>": "<YouTube or other link>"}).
 * Courses that had nothing but a recording (Session 7, Block of Chain) are created by running 23 again first.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

const RECORDING = '8';
const SOURCES = ['tc:recording', 'tc:recording_item', 'tc:preview:files', 'tc:preview:text_lessons', 'tc:description'];

/* ---------- undo ---------- */
if (in_array('--undo', $argv, true)) {
    $files = DB::table('cvh_import_map')->where('source', 'tc:recording')->pluck('new_id');
    foreach (DB::table('files')->whereIn('id', $files)->where('storage', 'upload')->pluck('file') as $path) @unlink(public_path($path));
    DB::table('webinar_chapter_items')->whereIn('id', DB::table('cvh_import_map')->where('source', 'tc:recording_item')->pluck('new_id'))->delete();
    DB::table('file_translations')->whereIn('file_id', $files)->delete();
    $removed = DB::table('files')->whereIn('id', $files)->delete();
    foreach (['files', 'text_lessons'] as $table) {
        foreach (DB::table('cvh_import_map')->where('source', "tc:preview:{$table}")->get() as $row) {
            DB::table($table)->where('id', $row->new_id)->update(['accessibility' => $row->prev]);
        }
    }
    foreach (DB::table('cvh_import_map')->where('source', 'tc:description')->get() as $row) {
        DB::table('file_translations')->where('file_id', $row->new_id)->where('locale', 'en')->update(['description' => $row->prev]);
    }
    DB::table('cvh_import_map')->whereIn('source', SOURCES)->delete();
    Cache::flush();
    exit("Removed {$removed} recordings and put the lessons' previews back as they were.\n");
}

$dir = rtrim($argv[1] ?? '', '/\\');
if (!$dir || !is_file("{$dir}/all_courses.json") || !is_file("{$dir}/materials.json")) {
    exit("Usage: php database/cvh/33_add_recordings.php <export-dir> [--apply]   (the folder with all_courses.json and materials.json)\n");
}
$apply = in_array('--apply', $argv, true);
$courses = json_decode(file_get_contents("{$dir}/all_courses.json"), true);
$materials = json_decode(file_get_contents("{$dir}/materials.json"), true);
$recordings = is_file("{$dir}/recordings.json") ? json_decode(file_get_contents("{$dir}/recordings.json"), true) : [];
foreach (glob("{$dir}/files/recordings/*.mp4") ?: [] as $path) {
    $recordings[pathinfo($path, PATHINFO_FILENAME)] ??= $path;
}

$map = fn(string $source, $oldId) => DB::table('cvh_import_map')->where('source', $source)->where('old_id', $oldId)->value('new_id');
$remember = function (string $source, $oldId, int $newId, ?string $prev = null) use ($apply) {
    if ($apply) DB::table('cvh_import_map')->insert(['source' => $source, 'old_id' => $oldId, 'new_id' => $newId, 'created' => 1, 'prev' => $prev]);
};
$now = time();
$counts = ['added' => 0, 'there' => 0, 'missing' => 0, 'no_course' => 0, 'previews' => 0, 'descriptions' => 0];

// TrainerCentral's lesson description (editor HTML) as plain text with its paragraphs
$plain = function (?string $html): string {
    $text = preg_replace(['#<br\s*/?>#i', '#</(p|div|li|h\d)>#i'], "\n", (string)$html);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace(["/[ \t\x{00A0}]+/u", "/\s*\n\s*/"], [' ', "\n\n"], $text));
};

foreach ($courses as $c) {
    $courseName = trim($c['course']['courseName'] ?? '');
    $free = collect($c['sessions']['sessionSettings'] ?? [])->where('allowFreePreview', 'true')->pluck('sessionId')->all();
    $about = collect($c['sessions']['sessions'] ?? [])->keyBy('id')->map(fn($s) => $plain($s['description'] ?? ''));
    $lessons = collect($c['sessionInfos']['sessions'] ?? [])->groupBy('section');

    foreach ($c['sections']['sections'] ?? [] as $section) {
        $chapter = DB::table('webinar_chapters')->where('id', $map('tc:webinar_chapters', $section['id']))->first();
        $sectionLessons = collect($lessons[$section['id']] ?? [])->sortBy(fn($l) => (int)$l['sessionIndex'])->values();

        // the chapter items the import made for a lesson (its text, slides and documents)
        $lessonItems = function ($lesson) use ($materials, $map, $chapter) {
            $items = collect();
            foreach ($materials[$lesson['id']]['materials'] ?? [] as $m) {
                foreach (['files' => 'file', 'text_lessons' => 'text_lesson'] as $table => $type) {
                    $id = $map("tc:{$table}", $m['materialId']);
                    $item = $id && $chapter ? DB::table('webinar_chapter_items')->where('chapter_id', $chapter->id)->where('type', $type)->where('item_id', $id)->first() : null;
                    if ($item) $items->push(['table' => $table, 'id' => $id, 'item' => $item]);
                }
            }
            return $items;
        };

        foreach ($sectionLessons as $index => $lesson) {
            $label = "{$courseName}  →  {$lesson['name']}";

            // free preview on TrainerCentral: free here too
            if ($chapter && in_array($lesson['id'], $free, true)) {
                foreach ($lessonItems($lesson) as $it) {
                    $prev = DB::table($it['table'])->where('id', $it['id'])->value('accessibility');
                    if ($prev === 'free') continue;
                    if ($apply) DB::table($it['table'])->where('id', $it['id'])->update(['accessibility' => 'free']);
                    $remember("tc:preview:{$it['table']}", $it['id'], $it['id'], $prev);
                    $counts['previews']++;
                }
            }

            // the lesson's description on its files (the import gave them only the lesson's name)
            $description = $about[$lesson['id']] ?? '';
            if ($chapter && $description !== '') {
                foreach ($lessonItems($lesson)->where('table', 'files') as $it) {
                    $prev = DB::table('file_translations')->where('file_id', $it['id'])->where('locale', 'en')->value('description');
                    if ($prev !== $lesson['name'] || $map('tc:description', $it['id'])) continue;
                    if ($apply) DB::table('file_translations')->where('file_id', $it['id'])->where('locale', 'en')->update(['description' => $description]);
                    $remember('tc:description', $it['id'], $it['id'], $prev);
                    $counts['descriptions']++;
                }
            }

            foreach ($materials[$lesson['id']]['materials'] ?? [] as $m) {
                if ($m['materialType'] !== RECORDING) continue;
                $recordingId = $m['recordingId'] ?? $m['materialId'];

                if (!$chapter) { echo "  NO COURSE  {$label}   (run 23_import_trainercentral.php --apply first)\n"; $counts['no_course']++; continue; }
                $imported = $map('tc:files', $m['materialId']);
                if ($map('tc:recording', $lesson['id']) || ($imported && DB::table('files')->where('id', $imported)->where('chapter_id', $chapter->id)->exists())) {
                    $counts['there']++;
                    continue;
                }
                $link = $recordings[$recordingId] ?? null;
                if (!$link) { echo "  MISSING    {$label}   (not downloaded: files/recordings/{$recordingId}.mp4)\n"; $counts['missing']++; continue; }

                // where it goes: before its lesson's items, else before the next lesson's, else at the end of the section
                $position = null;
                foreach ($sectionLessons->slice($index) as $next) {
                    $position = $lessonItems($next)->min(fn($it) => $it['item']->order);
                    if ($position) break;
                }
                $position = $position ?: ((int)DB::table('webinar_chapter_items')->where('chapter_id', $chapter->id)->max('order') + 1);

                $webinar = DB::table('webinars')->where('id', $chapter->webinar_id)->first();
                $isFree = in_array($lesson['id'], $free, true);
                if (is_file($link)) {
                    $rel = "/store/trainercentral/{$webinar->slug}/" . Str::slug($lesson['name']) . "-{$recordingId}.mp4";
                    [$storage, $volume, $type, $file] = ['upload', round(filesize($link) / 1048576, 2), 'mp4', $rel];
                } else {
                    $storage = preg_match('/youtu\.?be/i', $link) ? 'youtube' : (preg_match('/vimeo/i', $link) ? 'vimeo' : 'external_link');
                    [$volume, $type, $file] = [0, 'video', $link];
                }
                echo "  add        {$label}   (" . ($storage === 'upload' ? "{$volume} MB on this server" : $storage) . ($isFree ? ', free preview' : '') . ")\n";
                $counts['added']++;
                if (!$apply) continue;

                if ($storage === 'upload') {
                    // hard-linked into public/store (no second copy on disk) or copied
                    @mkdir(dirname(public_path($file)), 0755, true);
                    @unlink(public_path($file));
                    @link($link, public_path($file)) || copy($link, public_path($file));
                }
                DB::table('webinar_chapter_items')->where('chapter_id', $chapter->id)->where('order', '>=', $position)->increment('order');
                $fileId = DB::table('files')->insertGetId(['creator_id' => $chapter->user_id, 'webinar_id' => $webinar->id, 'chapter_id' => $chapter->id,
                    'accessibility' => $isFree ? 'free' : 'paid', 'downloadable' => 0, 'storage' => $storage, 'file' => $file, 'volume' => $volume,
                    'file_type' => $type, 'check_previous_parts' => 0, 'online_viewer' => 0, 'order' => $position, 'status' => 'active', 'created_at' => $now]);
                DB::table('file_translations')->insert(['file_id' => $fileId, 'locale' => 'en', 'title' => Str::limit($lesson['name'] . ' (recording)', 250, ''), 'description' => $description ?: $lesson['name']]);
                $itemId = DB::table('webinar_chapter_items')->insertGetId(['user_id' => $chapter->user_id, 'chapter_id' => $chapter->id, 'item_id' => $fileId,
                    'type' => 'file', 'order' => $position, 'created_at' => $now]);
                $remember('tc:recording', $lesson['id'], $fileId);
                $remember('tc:recording_item', $lesson['id'], $itemId);
            }
        }
    }
}

if ($apply) Cache::flush();
echo "\n{$counts['added']} recordings " . ($apply ? 'added' : 'to add') . ", {$counts['there']} already there, {$counts['missing']} not downloaded yet, "
    . "{$counts['no_course']} in courses not on the site yet; {$counts['previews']} lesson items " . ($apply ? 'made' : 'to make') . " free previews; "
    . "{$counts['descriptions']} lesson files " . ($apply ? 'given' : 'to give') . " their lesson's description.\n";
if (!$apply && ($counts['added'] || $counts['previews'] || $counts['descriptions'])) echo "Run again with --apply to do it.\n";
