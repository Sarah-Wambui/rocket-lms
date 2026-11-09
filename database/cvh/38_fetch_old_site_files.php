<?php
/**
 * CVH Africa — download the files that the old-site import (script 14) links to but never received.
 * Courses and people imported from the old site point at /store/oldcvh/...; when that file is absent the
 * image is broken. The files are still online on the old site (https://cvh.africa/store/...).
 *
 *   php database/cvh/38_fetch_old_site_files.php --dry-run   show what is missing, change nothing
 *   php database/cvh/38_fetch_old_site_files.php             download course images and people's photos
 *   php database/cvh/38_fetch_old_site_files.php --all       also every file in storage/app/cvh_import_missing_files.txt
 *                                                            (lesson images, PDFs and other attachments)
 * Existing files are never overwritten and the database is not changed, so it is safe to re-run.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

const OLD_SITE = 'https://cvh.africa/store/';
const PREFIX = '/store/oldcvh/';

$dryRun = in_array('--dry-run', $argv, true);
$all = in_array('--all', $argv, true);

$sources = ['webinars' => ['thumbnail', 'image_cover'], 'users' => ['avatar', 'cover_img']];
$paths = [];
foreach ($sources as $table => $columns) {
    foreach ($columns as $column) {
        if (!Schema::hasColumn($table, $column)) {
            continue;
        }
        foreach (DB::table($table)->where($column, 'like', PREFIX . '%')->distinct()->pluck($column) as $path) {
            $paths[$path] = true;
        }
    }
}

$listFile = storage_path('app/cvh_import_missing_files.txt');
$others = 0;
if (is_file($listFile)) {
    foreach (file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if (!str_starts_with($line, '/store/')) {
            continue;
        }
        $path = str_starts_with($line, PREFIX) ? $line : PREFIX . substr($line, strlen('/store/'));
        if ($all) {
            $paths[$path] = true;
        } elseif (!isset($paths[$path]) && !is_file(public_path(ltrim($path, '/')))) {
            $others++;
        }
    }
}

$done = $present = $failed = 0;
foreach (array_keys($paths) as $path) {
    $relative = substr($path, strlen(PREFIX));
    if (in_array('..', explode('/', $relative), true)) {
        echo "SKIP unsafe path: $path\n";
        continue;
    }

    $target = public_path(ltrim($path, '/'));
    if (is_file($target)) {
        $present++;
        continue;
    }
    if ($dryRun) {
        echo "MISSING $path\n";
        continue;
    }

    @mkdir(dirname($target), 0775, true);
    $part = $target . '.part';
    $url = OLD_SITE . implode('/', array_map('rawurlencode', explode('/', $relative)));

    try {
        $response = Http::timeout(300)->sink($part)->get($url);
        $isPage = str_contains(strtolower($response->header('Content-Type')), 'text/html');
        if (!$response->successful() || $isPage || !filesize($part)) {
            throw new RuntimeException('HTTP ' . $response->status());
        }
        rename($part, $target);
        echo "OK   $path\n";
        $done++;
    } catch (Throwable $e) {
        @unlink($part);
        echo "FAIL $path (" . $e->getMessage() . ")\n";
        $failed++;
    }
}

echo "\n$done downloaded, $present already there, $failed failed.\n";
if ($others) {
    echo "$others other imported files (lesson images, PDFs, attachments) are also missing: add --all to download them.\n";
}
