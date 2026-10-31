<?php
/**
 * CVH Africa — remove the template's course categories (Design, Academics, Business, Development, Health & Fitness,
 * Lifestyle, Marketing and their subcategories) so only CVH's four remain: Food Safety, Agripreneurship,
 * Climate & Agriculture, Aquaculture & Circularity. 02_content.php only switched them off, but the instructor's
 * course form still listed them.
 *
 *   php database/cvh/30_remove_demo_categories.php           show what would change
 *   php database/cvh/30_remove_demo_categories.php --apply   change it (take a backup first: backup_database.php)
 *
 * The database deletes everything filed under a category together with it, so courses, bundles, upcoming courses
 * and events still in a template category are first moved into a CVH category by their title (same rules as
 * 25_events_jobs_categories.php). What goes with the template categories: their translations, filters, trending
 * entries, and instructors' "skills" pointing at them.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CVH_CATEGORIES = ['Food-Safety', 'Agripreneurship', 'Climate-and-Agriculture', 'Aquaculture'];

$apply = in_array('--apply', $argv, true);

$cat = DB::table('categories')->whereIn('slug', CVH_CATEGORIES)->pluck('id', 'slug');
if (count($cat) < count(CVH_CATEGORIES)) {
    exit("Not all four CVH categories exist (found: " . $cat->keys()->implode(', ') . "). Run 02_content.php first.\n");
}
$keep = $cat->values()->merge(DB::table('categories')->whereIn('parent_id', $cat->values())->pluck('id'))->all();
$demo = DB::table('categories')->whereNotIn('id', $keep)->pluck('id')->all();

$title = fn($id) => DB::table('category_translations')->where('category_id', $id)->orderByRaw("locale = 'en' desc")->value('title')
    ?? DB::table('categories')->where('id', $id)->value('slug');
$byTitle = function (string $title) use ($cat) {
    if (preg_match('/aqua|fish|insect|spirulina|circular/i', $title)) return $cat['Aquaculture'];
    if (preg_match('/climate|sustainab|environment|soil|water/i', $title)) return $cat['Climate-and-Agriculture'];
    if (preg_match('/food|safety|nutri|diet|toxin|metal|hygiene|acrylamide|processing/i', $title)) return $cat['Food-Safety'];
    return $cat['Agripreneurship'];
};

echo "Keeping: " . collect($keep)->map($title)->implode(', ') . "\n\n";
if (!$demo) {
    exit("No template categories left. Nothing to do.\n");
}

echo "== Template categories to delete (" . count($demo) . ")\n";
foreach (DB::table('categories')->whereIn('id', $demo)->orderByRaw('coalesce(parent_id, id), id')->get(['id', 'parent_id']) as $c) {
    echo '  ' . ($c->parent_id ? '  - ' : '') . $title($c->id) . "\n";
}

// items filed under a template category: moved into a CVH category, never deleted
$moves = [];
foreach (['webinars' => 'webinar', 'bundles' => 'bundle', 'upcoming_courses' => 'upcoming_course', 'events' => 'event'] as $table => $item) {
    if (!Schema::hasTable($table) or !Schema::hasColumn($table, 'category_id')) continue;
    $rows = DB::table("{$table} as i")
        ->leftJoin("{$item}_translations as t", fn($j) => $j->on("t.{$item}_id", '=', 'i.id')->where('t.locale', 'en'))
        ->whereIn('i.category_id', $demo)
        ->get(['i.id', 'i.slug', 'i.category_id', 't.title']);
    foreach ($rows as $row) {
        $name = $row->title ?? $row->slug;
        $moves[] = [$table, $row->id, $byTitle($name)];
        printf("  move %-16s %-55s %s -> %s\n", rtrim($table, 's'), mb_strimwidth($name, 0, 55, '…'), $title($row->category_id), $title($byTitle($name)));
    }
}
echo $moves ? '' : "\nNo courses, bundles, upcoming courses or events are in a template category.\n";

// what the database deletes along with the template categories
echo "\nGoes with them:\n";
foreach (DB::select("select k.table_name t, k.column_name c from information_schema.key_column_usage k
        where k.constraint_schema = database() and k.referenced_table_name = 'categories' and k.referenced_column_name = 'id'") as $fk) {
    if (in_array($fk->t, ['webinars', 'bundles', 'upcoming_courses', 'events', 'categories'])) continue;
    $n = DB::table($fk->t)->whereIn($fk->c, $demo)->count();
    if ($n) printf("  %-45s %d\n", $fk->t, $n);
}

if (!$apply) {
    exit("\nNothing changed. Back up the database, then run again with --apply.\n");
}

DB::transaction(function () use ($moves, $demo) {
    foreach ($moves as [$table, $id, $categoryId]) {
        DB::table($table)->where('id', $id)->update(['category_id' => $categoryId]);
    }
    // subcategories first, then their parents
    DB::table('categories')->whereIn('id', $demo)->whereNotNull('parent_id')->delete();
    DB::table('categories')->whereIn('id', $demo)->delete();
});
Cache::flush();

echo "\nDone: moved " . count($moves) . " items into CVH categories and deleted " . count($demo) . " template categories.\n";
