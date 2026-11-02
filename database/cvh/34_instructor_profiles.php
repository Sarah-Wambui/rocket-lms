<?php
/**
 * CVH Africa — instructor profile pages and "Send message".
 *
 *   php database/cvh/34_instructor_profiles.php           show what would change
 *   php database/cvh/34_instructor_profiles.php --apply   change it
 *
 * Users imported straight into the database (old site, TrainerCentral, the CVH instructors) have no username, and
 * profile links are /users/{username}/profile, so every instructor card linked to "/users//profile" (a 404).
 * This gives them a username the same way the site does for new users (name + random letters), and switches on
 * "Send message" for instructors and organisations so learners can contact them from the profile page. Messages to
 * instructors with a placeholder e-mail (...@instructors.cvh.local) go to the site e-mail (Settings -> General).
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);

$noUsername = User::query()->whereNull('username')->orWhere('username', '')->get(['id', 'full_name', 'role_name']);
$silent = User::query()->whereIn('role_name', ['teacher', 'organization'])->where('public_message', false)->get(['id', 'full_name']);

printf("Users without a username: %d (%d instructors)\n", $noUsername->count(), $noUsername->where('role_name', 'teacher')->count());
foreach ($noUsername->where('role_name', 'teacher') as $user) {
    echo "  - {$user->full_name}\n";
}
printf("Instructors/organisations with Send message off: %d\n", $silent->count());

if ($noUsername->isEmpty() and $silent->isEmpty()) {
    exit("Nothing to do.\n");
}
if (!$apply) {
    exit("\nNothing changed. Run again with --apply.\n");
}

DB::transaction(function () use ($noUsername, $silent) {
    foreach ($noUsername as $user) {
        $user->username = null;
        $user->getUsername(); // makes and saves one (App\User, marked CVH)
    }

    User::query()->whereIn('id', $silent->pluck('id'))->update(['public_message' => true]);
});

Cache::flush();
echo "\nDone. Instructor cards now link to their profile pages (courses, about, Send message).\n";
