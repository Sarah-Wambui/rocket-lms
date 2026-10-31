<?php
/**
 * CVH Africa — back up the LMS database to a .sql file, with the login the site itself uses (config/database.php,
 * i.e. .env), so a password with special characters works (reading .env in the shell mangled it: "Access denied").
 *
 *   php database/cvh/backup_database.php                 writes ~/lms-backup-<date>-<time>.sql
 *   php database/cvh/backup_database.php /path/file.sql
 *   php database/cvh/backup_database.php --bin=/path/to/mysqldump   when mariadb-dump / mysqldump is not on the PATH
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$args = array_slice($argv, 1);
$bin = null;
foreach ($args as $i => $arg) {
    if (str_starts_with($arg, '--bin=')) {
        $bin = substr($arg, 6);
        unset($args[$i]);
    }
}
$home = getenv('HOME') ?: getenv('USERPROFILE') ?: base_path();
$out = array_values($args)[0] ?? ($home . '/lms-backup-' . date('Y-m-d-Hi') . '.sql');

if (!$bin) {
    foreach (['mariadb-dump', 'mysqldump'] as $candidate) {
        $path = trim((string)shell_exec('command -v ' . $candidate . ' 2>/dev/null'));
        if ($path) {
            $bin = $path;
            break;
        }
    }
}
if (!$bin) {
    exit("Neither mariadb-dump nor mysqldump was found. Take the backup in the hosting control panel instead.\n");
}

$db = config('database.connections.' . config('database.default'));

// the login goes in a private options file, not on the command line (keeps the password out of the process list)
$quote = fn($v) => '"' . addcslashes((string)$v, "\\\"") . '"';
$options = tempnam(storage_path('app'), 'dump');
chmod($options, 0600);
file_put_contents($options, "[client]\nuser={$quote($db['username'])}\npassword={$quote($db['password'])}\nhost={$quote($db['host'])}\nport={$quote($db['port'])}\n");

$command = [$bin, '--defaults-extra-file=' . $options, '--single-transaction', '--quick', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4', '--result-file=' . $out, $db['database']];
$process = proc_open($command, [2 => ['pipe', 'w']], $pipes);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$code = proc_close($process);
unlink($options);

clearstatcache();
$size = is_file($out) ? filesize($out) : 0;
if ($code !== 0 or $size < 1024) {
    echo "Backup FAILED (exit code {$code}):\n{$errors}\n";
    exit(1);
}
printf("Backup OK: %s (%.1f MB)\n", $out, $size / 1048576);
