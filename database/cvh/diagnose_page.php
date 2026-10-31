<?php
/**
 * CVH Africa — find out why a page is slow or runs out of memory: opens the page as a given user (with a high memory
 * limit) and prints its status, peak memory, number of database queries and the queries repeated most often.
 * Changes nothing (the login-session check is skipped, so the user is not logged out).
 * "latest" in the path is replaced by the id of the user's most recently updated course.
 *
 *   php -d memory_limit=2G database/cvh/diagnose_page.php /panel/courses 1056
 *   php -d memory_limit=2G database/cvh/diagnose_page.php /panel/courses/latest/edit someone@example.com   (user id or email)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';

[$path, $userArg] = [$argv[1] ?? '/panel/courses', $argv[2] ?? ''];

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// the login-session check would see no real login, log the user out and redirect
$app->instance(App\Http\Middleware\SessionValidity::class, new class {
    public function handle($request, $next) { return $next($request); }
});

$user = str_contains($userArg, '@') ? App\User::where('email', $userArg)->first() : App\User::find((int)$userArg);
if (!$user) {
    exit("No user {$userArg}\n");
}

if (str_contains($path, 'latest')) {
    $latest = App\Models\Webinar::query()->where(fn($q) => $q->where('creator_id', $user->id)->orWhere('teacher_id', $user->id))->orderByDesc('updated_at')->first();
    if (!$latest) {
        exit("User {$user->id} has no courses\n");
    }
    $path = str_replace('latest', $latest->id, $path);
    printf("Latest course: %d %s\n", $latest->id, $latest->title);
}

$request = Illuminate\Http\Request::create($path, 'GET');
printf("Code: %s\nUser %d: %s (%s)\nPage: %s\n\n", trim((string)shell_exec('git log --oneline -1')), $user->id, $user->full_name, $user->role_name, $path);

Illuminate\Support\Facades\DB::enableQueryLog();
auth()->setUser($user);
$started = microtime(true);
$response = $kernel->handle($request);

$queries = Illuminate\Support\Facades\DB::getQueryLog();
$peak = memory_get_peak_usage(true) / 1048576;
printf("Status: %d   Time: %.1fs   Peak memory: %d MB   Queries: %d\n", $response->getStatusCode(), microtime(true) - $started, $peak, count($queries));
if ($peak > 128) {
    echo "!! Over 128 MB: the live site (memory_limit 128M) would fail with 'Allowed memory size exhausted'.\n";
}
if (!empty($response->exception)) {
    $e = $response->exception;
    printf("Error: %s: %s\n", get_class($e), $e->getMessage());
    // where in our code / templates it happened (compiled views are shown as the .blade.php they came from)
    $root = str_replace('\\', '/', base_path()) . '/';
    $where = function ($file, $line) use ($root) {
        $file = str_replace('\\', '/', $file);
        if (str_contains($file, '/storage/framework/views/') and preg_match('#/\*\*PATH (.+?) ENDPATH\*\*/#', (string)@file_get_contents($file), $m)) {
            return str_replace([str_replace('/', '\\', $root), $root], '', $m[1]) . ' (compiled line ' . $line . ')';
        }
        return str_replace($root, '', $file) . ':' . $line;
    };
    echo "  at " . $where($e->getFile(), $e->getLine()) . "\n";
    foreach (array_slice(array_filter($e->getTrace(), fn($f) => !empty($f['file']) and !str_contains(str_replace('\\', '/', $f['file']), '/vendor/')), 0, 6) as $f) {
        echo "  from " . $where($f['file'], $f['line']) . "\n";
    }
}

echo "\nMost repeated queries:\n";
collect($queries)
    ->countBy(fn($q) => mb_strimwidth(preg_replace('/\s+/', ' ', $q['query']), 0, 160, '…'))
    ->sortDesc()->take(8)
    ->each(function ($n, $sql) { printf("%6d x %s\n", $n, $sql); });

echo "\nSlowest queries:\n";
collect($queries)->sortByDesc('time')->take(3)
    ->each(function ($q) { printf("%7.0f ms  %s\n", $q['time'], mb_strimwidth(preg_replace('/\s+/', ' ', $q['query']), 0, 160, '…')); });
