<?php
/**
 * CVH Africa — find admin sidebar sections that lead to a licence page (Plugins Bundle / Theme Builder /
 * Mobile App) on THIS server, and optionally hide them for the admin role so nobody lands on those pages.
 * Nothing is unlocked or deleted: hiding = setting the admin role's permission for those sections to "deny".
 *
 * Run on the LIVE server from the project root (it checks the site over the web at APP_URL):
 *   php database/cvh/10_admin_locked_sections.php           report only (no changes)
 *   php database/cvh/10_admin_locked_sections.php --apply   report + hide the locked sections
 *   php database/cvh/10_admin_locked_sections.php --undo    show everything that --apply hid again
 *
 * How it checks: creates a temporary admin account with a random password, logs into /admin over HTTPS,
 * opens every sidebar link without following redirects, notes which ones redirect to /admin/licenses/...,
 * then deletes the temporary account.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

const ADMIN_ROLE_ID = 2;
$undoFile = storage_path('app/cvh_hidden_admin_sections.json');
$mode = $argv[1] ?? '--report';

/* ---------- --undo ---------- */
if ($mode === '--undo') {
    if (!is_file($undoFile)) {
        exit("Nothing to undo ({$undoFile} not found).\n");
    }
    $saved = json_decode(file_get_contents($undoFile), true);
    foreach ($saved as $sectionId => $allow) {
        DB::table('permissions')->where('role_id', ADMIN_ROLE_ID)->where('section_id', $sectionId)->update(['allow' => $allow]);
    }
    unlink($undoFile);
    exit('Restored ' . count($saved) . " admin sections.\n");
}

/* ---------- Map sidebar links -> permission sections (from the sidebar Blade files) ---------- */
$prefix = '/' . trim(getAdminPanelUrl(null, false), '/');
$linkSections = [];   // path => [outer..inner section names]
$sectionLinks = [];   // section name => [paths under it]
foreach (glob(resource_path('views/admin/includes/sidebar/*.blade.php')) as $file) {
    $stack = [];
    foreach (file($file) as $line) {
        if (preg_match_all("/@can\\(['\"]([a-z0-9_]+)['\"]\\)|@endcan|href=\"\\{\\{\\s*(getAdminPanelUrl|getLandingBuilderUrl)\\((?:['\"]([^'\"]*)['\"])?\\)\\s*\\}\\}([^\"]*)\"/", $line, $m, PREG_SET_ORDER)) {
            foreach ($m as $t) {
                if (!empty($t[1])) {
                    $stack[] = $t[1];
                } elseif ($t[0] === '@endcan') {
                    array_pop($stack);
                } elseif (!empty($t[2])) {
                    $base = $t[2] === 'getLandingBuilderUrl' ? $prefix . '/landing-builder' : $prefix;
                    $path = rtrim($base . ($t[3] ?? '') . ($t[4] ?? ''), '/');
                    if (str_contains($path, '{') || $path === $prefix) continue;
                    $linkSections[$path] = $stack;
                    foreach ($stack as $s) $sectionLinks[$s][$path] = true;
                }
            }
        }
    }
}

/* ---------- Check every link over HTTP as a temporary admin ---------- */
$baseUrl = rtrim(config('app.url'), '/');
$email = 'cvh-audit-' . Str::random(8) . '@example.invalid';
$password = Str::random(24);
$tempUserId = DB::table('users')->insertGetId([
    'full_name' => 'CVH temporary audit', 'role_name' => 'admin', 'role_id' => ADMIN_ROLE_ID, 'email' => $email,
    'password' => Hash::make($password), 'status' => 'active', 'verified' => 1, 'created_at' => time(),
]);

$locked = [];   // path => licence type
try {
    $jar = new CookieJar();
    $http = new Client(['base_uri' => $baseUrl, 'cookies' => $jar, 'allow_redirects' => false, 'http_errors' => false, 'timeout' => 60,
        'headers' => ['User-Agent' => 'Mozilla/5.0 (CVH admin audit)']]);

    $loginPage = (string)$http->get($prefix . '/login')->getBody();
    if (!preg_match('/name="_token"\s+value="([^"]+)"/', $loginPage, $tok)) {
        throw new RuntimeException("Could not read the admin login form at {$baseUrl}{$prefix}/login");
    }
    $res = $http->post($prefix . '/login', ['form_params' => ['_token' => $tok[1], 'email' => $email, 'password' => $password]]);
    $dash = $http->get($prefix);
    if ($dash->getStatusCode() !== 200) {
        throw new RuntimeException('Admin login failed (dashboard returned ' . $dash->getStatusCode() . ' -> ' . $dash->getHeaderLine('Location') . ')');
    }

    echo 'Checking ' . count($linkSections) . " admin links on {$baseUrl} ...\n";
    foreach (array_keys($linkSections) as $path) {
        $r = $http->get($path);
        $location = $r->getHeaderLine('Location');
        if (preg_match('#/licenses/([a-z_-]+)#', $location, $lm)) {
            $locked[$path] = $lm[1];
        }
    }
} finally {
    DB::table('user_login_histories')->where('user_id', $tempUserId)->delete();
    DB::table('users')->where('id', $tempUserId)->delete();
}

/* ---------- Work out which permission sections to hide ---------- */
$hide = [];
foreach ($locked as $path => $type) {
    $stack = $linkSections[$path];
    if ($stack) $hide[end($stack)] = $type;                // innermost permission guarding the link
}
foreach ($sectionLinks as $section => $paths) {              // hide a parent group when every link inside it is locked
    if (count(array_diff_key($paths, $locked)) === 0) {
        $hide[$section] = $hide[$section] ?? reset($locked);
    }
}

if (!$locked) {
    exit("No admin pages redirect to a licence page. Nothing to hide.\n");
}

echo "\nLocked admin pages (" . count($locked) . "):\n";
foreach ($locked as $path => $type) echo "  [{$type}] {$path}\n";

$captions = DB::table('sections')->whereIn('name', array_keys($hide))->pluck('caption', 'name');
echo "\nAdmin sections to hide (" . count($hide) . "):\n";
foreach ($hide as $name => $type) echo "  {$name}  ({$captions[$name]})\n";

if ($mode !== '--apply') {
    exit("\nReport only. Run again with --apply to hide these sections (undo any time with --undo).\n");
}

$sectionIds = DB::table('sections')->whereIn('name', array_keys($hide))->pluck('id');
$previous = is_file($undoFile) ? json_decode(file_get_contents($undoFile), true) : [];
foreach (DB::table('permissions')->where('role_id', ADMIN_ROLE_ID)->whereIn('section_id', $sectionIds)->get() as $p) {
    $previous[$p->section_id] = $previous[$p->section_id] ?? $p->allow;
}
file_put_contents($undoFile, json_encode($previous));
DB::table('permissions')->where('role_id', ADMIN_ROLE_ID)->whereIn('section_id', $sectionIds)->update(['allow' => 0]);

echo "\nHidden " . count($sectionIds) . " sections for the admin role. Undo with: php database/cvh/10_admin_locked_sections.php --undo\n";
