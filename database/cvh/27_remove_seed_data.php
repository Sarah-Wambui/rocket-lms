<?php
/**
 * CVH Africa — remove the rest of the template's seed content (22_remove_demo_data.php removed the demo users and
 * courses): demo forums, meetings, store products, installments, forms, coupons, subscription plans, packages,
 * notifications, orders, newsletters, banners, testimonials, ...
 *
 *   php database/cvh/27_remove_seed_data.php           show what would be deleted
 *   php database/cvh/27_remove_seed_data.php --apply   delete it (take a database backup first)
 *
 * A row is seed only when it is still the template's own row: same id AND same created date / title / owner / ...
 * as in the template's demo database (fingerprints in seed_fingerprints.json, made from Databases/demo_db.sql).
 * Real CVH rows that happen to have the same id never match. A seed row that real data still points to (through a
 * foreign key that would delete that real data with it) is kept and listed.
 * Not touched: the configuration the site runs on (settings, roles, page sections, notification templates, regions,
 * payment channels, badge/reward rules, support departments, certificate templates, categories).
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$apply = in_array('--apply', $argv, true);
$fingerprints = json_decode(file_get_contents(__DIR__ . '/seed_fingerprints.json'), true);
$normalize = fn($v) => is_null($v) ? null : ((is_string($v) and mb_strlen($v) > 120) ? ('md5:' . md5($v)) : (string)$v);

// 1. live rows that are still the template's seed rows
$delete = [];
foreach ($fingerprints as $table => $fp) {
    if (!Schema::hasTable($table)) continue;
    $columns = array_values(array_intersect($fp['columns'], Schema::getColumnListing($table)));
    if (count($columns) < 2) continue;

    $seed = [];
    foreach ($fp['rows'] as $row) {
        $row = array_combine($fp['columns'], $row);
        $seed[$row['id']] = array_map($normalize, array_intersect_key($row, array_flip($columns)));
    }

    $ids = [];
    foreach (array_chunk(array_keys($seed), 1000) as $chunk) {
        foreach (DB::table($table)->whereIn('id', $chunk)->get($columns) as $live) {
            if (array_map($normalize, (array)$live) == $seed[$live->id]) $ids[] = $live->id;
        }
    }
    if ($ids) $delete[$table] = $ids;
}

// 1b. the template ships slightly different seed rows per version, so also: dated before CVH's site existed and never
// edited since, in the tables the old-site import (14_import_old_site.php) never wrote to (its rows keep old dates)
const CUTOFF = 1767225600; // 2026-01-01
$neverImported = ['forums', 'forum_topics', 'forum_topic_posts', 'meetings', 'reserve_meetings', 'products', 'product_categories',
    'product_discounts', 'product_files', 'product_filters', 'product_media', 'product_orders', 'product_reviews', 'product_specifications',
    'installments', 'installment_steps', 'installment_orders', 'noticeboards', 'forms', 'form_submissions', 'groups', 'discounts', 'promotions',
    'special_offers', 'cashback_rules', 'affiliates', 'affiliates_codes', 'become_instructors', 'delete_account_requests', 'ip_restrictions',
    'offline_payments', 'subscribes', 'subscribe_uses', 'registration_packages', 'newsletters', 'newsletters_history', 'floating_bars',
    'advertising_banners', 'testimonials', 'blog_categories', 'comments', 'orders', 'order_items', 'rewards_accounting', 'notifications', 'ai_contents'];
// unix-time columns compare with CUTOFF, date columns with the date
$cutoffFor = fn($table, $column) => in_array(DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
    ->where('table_name', $table)->where('column_name', $column)->value('data_type'), ['int', 'bigint', 'mediumint']) ? CUTOFF : '2026-01-01';
foreach ($neverImported as $table) {
    if (!Schema::hasTable($table) or !Schema::hasColumn($table, 'created_at')) continue;
    $query = DB::table($table)->where('created_at', '<', $cutoffFor($table, 'created_at'));
    if (Schema::hasColumn($table, 'updated_at')) {
        $updatedCutoff = $cutoffFor($table, 'updated_at');
        $query->where(fn($q) => $q->whereNull('updated_at')->orWhere('updated_at', '<', $updatedCutoff));
    }
    $ids = $query->pluck('id')->all();
    if ($ids) $delete[$table] = array_values(array_unique(array_merge($delete[$table] ?? [], $ids)));
}

// 2. keep seed rows that real data depends on (a cascading foreign key from a row we are not deleting).
// These tables only hold parts of the item they point to (read marks, photos, translations, featured/top links), so they go with it.
$partsOfItem = ['notifications_status', 'noticeboards_status', 'product_media', 'product_selected_filter_options', 'product_filter_options',
    'forum_featured_topics', 'forum_recommended_topics', 'forum_recommended_topic_items', 'discount_categories', 'discount_courses',
    'discount_bundles', 'discount_groups', 'discount_users', 'installment_specification_items', 'advertising_banners_translations',
    'group_users', 'meeting_times', 'reserve_meetings', 'product_featured_categories', 'abandoned_cart_rules'];
$cascades = DB::select("select k.table_name child, k.column_name col, k.referenced_table_name parent
    from information_schema.key_column_usage k
    join information_schema.referential_constraints r on r.constraint_schema = k.constraint_schema and r.constraint_name = k.constraint_name
    where k.constraint_schema = database() and k.referenced_column_name = 'id' and r.delete_rule = 'CASCADE'");
$kept = [];
do {
    $changed = false;
    foreach ($cascades as $fk) {
        if (empty($delete[$fk->parent]) or in_array($fk->child, $partsOfItem) or preg_match('/_translations$|featured|_top_categories$|^cart_discounts$/', $fk->child)) continue;
        $query = DB::table($fk->child)->whereIn($fk->col, $delete[$fk->parent]);
        if (!empty($delete[$fk->child]) and Schema::hasColumn($fk->child, 'id')) {
            $query->whereNotIn('id', $delete[$fk->child]);
        }
        $needed = $query->distinct()->pluck($fk->col)->all();
        if ($needed) {
            $delete[$fk->parent] = array_values(array_diff($delete[$fk->parent], $needed));
            foreach ($needed as $id) $kept[$fk->parent][$id] = "{$fk->child}.{$fk->col}";
            $changed = true;
        }
    }
} while ($changed);

// blog categories still used by CVH posts
if (!empty($delete['blog_categories']) and Schema::hasTable('blog')) {
    $used = DB::table('blog')->whereIn('category_id', $delete['blog_categories'])->distinct()->pluck('category_id')->all();
    $delete['blog_categories'] = array_values(array_diff($delete['blog_categories'], $used));
    foreach ($used as $id) $kept['blog_categories'][$id] = 'blog.category_id';
}
$delete = array_filter($delete);

// 3. report
$label = function ($table, $id) {
    foreach (['title', 'name', 'slug', 'email', 'code', 'subject'] as $c) {
        if (Schema::hasColumn($table, $c)) return mb_strimwidth((string)DB::table($table)->where('id', $id)->value($c), 0, 60, '…');
    }
    return '';
};
$total = 0;
foreach ($delete as $table => $ids) {
    $total += count($ids);
    printf("%-50s %5d", $table, count($ids));
    $examples = array_filter(array_map(fn($id) => $label($table, $id), array_slice($ids, 0, 3)));
    echo $examples ? '   e.g. ' . implode(' | ', $examples) : '', "\n";
}
echo "\nSeed rows to delete: {$total}\n";

if ($kept) {
    echo "\nKept because real data uses them:\n";
    foreach ($kept as $table => $rows) {
        foreach ($rows as $id => $by) echo "  {$table} {$id} " . $label($table, $id) . " (used by {$by})\n";
    }
}

if (!$apply) {
    exit("\nNothing deleted. Back up the database, then run again with --apply.\n");
}

DB::transaction(function () use ($delete) {
    foreach ($delete as $table => $ids) {
        foreach (array_chunk($ids, 1000) as $chunk) DB::table($table)->whereIn('id', $chunk)->delete();
    }
});
Cache::flush();

echo "\nDone: deleted {$total} seed rows.\n";
