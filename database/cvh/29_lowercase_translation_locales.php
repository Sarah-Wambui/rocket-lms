<?php
/**
 * CVH Africa — fix translations saved with the language in capitals ("EN" instead of "en").
 *
 *   php database/cvh/29_lowercase_translation_locales.php           show what would change
 *   php database/cvh/29_lowercase_translation_locales.php --apply   change it
 *
 * The course content forms sent the site's default language as "EN" and the panel saved it as it came (live
 * sessions, files, text lessons, sections, quizzes, FAQs, ...). The site reads translations as "en", never found
 * these, and its fallback looped until memory ran out — the 500 on /panel/courses after adding a live session.
 * New ones are now saved lowercase (UserLocale middleware); this fixes the ones already saved. Where the item also
 * has a lowercase translation in that language, the capitalised duplicate is removed instead.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$apply = in_array('--apply', $argv, true);

$tables = DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
    ->where('column_name', 'locale')->where('table_name', 'like', '%\_translations')->selectRaw('table_name as name')->pluck('name');

$fixed = $duplicates = 0;
foreach ($tables as $table) {
    $rows = DB::table($table)->whereRaw('BINARY `locale` <> LOWER(`locale`)')->get();
    if ($rows->isEmpty()) continue;

    // the column pointing to the translated item (e.g. session_id in session_translations)
    $parent = collect(Schema::getColumnListing($table))->first(fn($c) => str_ends_with($c, '_id'));

    $toFix = $toDelete = [];
    foreach ($rows as $row) {
        $hasLowercase = $parent && DB::table($table)->where($parent, $row->{$parent})->whereRaw('BINARY `locale` = ?', [mb_strtolower($row->locale)])->exists();
        if ($hasLowercase) $toDelete[] = $row->id; else $toFix[] = $row->id;
    }

    printf("%-45s %4d to lowercase, %4d duplicates to remove\n", $table, count($toFix), count($toDelete));
    $fixed += count($toFix);
    $duplicates += count($toDelete);

    if ($apply) {
        DB::table($table)->whereIn('id', $toDelete)->delete();
        DB::table($table)->whereIn('id', $toFix)->update(['locale' => DB::raw('LOWER(`locale`)')]);
    }
}

if (!$fixed and !$duplicates) {
    exit("All translations already use lowercase languages. Nothing to do.\n");
}
if (!$apply) {
    exit("\nNothing changed. Run again with --apply.\n");
}
Cache::flush();
echo "\nDone: {$fixed} translations lowercased, {$duplicates} duplicates removed.\n";
