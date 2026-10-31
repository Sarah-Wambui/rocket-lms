<?php
/**
 * CVH Africa — find out why a page is slow or runs out of memory: opens the page as a given user (with a high memory
 * limit) and prints its status, peak memory, number of database queries and the queries repeated most often.
 * Changes nothing.
 *
 *   php -d memory_limit=2G database/cvh/diagnose_page.php /panel/courses 1056
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';

[$path, $userId] = [$argv[1] ?? '/panel/courses', (int)($argv[2] ?? 0)];

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create($path, 'GET');
$app->instance('request', $request);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\User::find($userId);
if (!$user) {
    exit("No user {$userId}\n");
}
printf("Code: %s\nUser %d: %s (%s)\nPage: %s\n\n", trim((string)shell_exec('git log --oneline -1')), $user->id, $user->full_name, $user->role_name, $path);

Illuminate\Support\Facades\DB::enableQueryLog();
auth()->setUser($user);
$started = microtime(true);
$response = $kernel->handle($request);

$queries = Illuminate\Support\Facades\DB::getQueryLog();
printf("Status: %d   Time: %.1fs   Peak memory: %d MB   Queries: %d\n", $response->getStatusCode(), microtime(true) - $started, memory_get_peak_usage(true) / 1048576, count($queries));
if (!empty($response->exception)) {
    printf("Error: %s\n  at %s:%d\n", $response->exception->getMessage(), $response->exception->getFile(), $response->exception->getLine());
}

echo "\nMost repeated queries:\n";
collect($queries)
    ->countBy(fn($q) => mb_strimwidth(preg_replace('/\s+/', ' ', $q['query']), 0, 160, '…'))
    ->sortDesc()->take(8)
    ->each(function ($n, $sql) { printf("%6d x %s\n", $n, $sql); });

echo "\nSlowest queries:\n";
collect($queries)->sortByDesc('time')->take(3)
    ->each(function ($q) { printf("%7.0f ms  %s\n", $q['time'], mb_strimwidth(preg_replace('/\s+/', ' ', $q['query']), 0, 160, '…')); });
