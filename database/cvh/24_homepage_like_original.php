<?php
/**
 * CVH Africa — homepage sections in the order and style of the original Rocket LMS demo (lms.rocket-soft.org):
 *   Hero, Statistics, Featured Courses, Trending Categories (now flowing across the page, where the partner strip was),
 *   Bestsellers (back on), Upcoming Courses (new), Free Courses, Expert Instructors, Official Organizations
 *   (the partner logos as cards), then any other sections already on the page, then the Blog.
 * Each section keeps the spacer that follows it. Sections not in that list keep their on/off state.
 * Run from the project root:  php database/cvh/24_homepage_like_original.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const ORDER = [
    'two_columns_hero', 'statistics', 'featured_courses', 'trending_categories', 'best_selling_courses',
    'upcoming_courses', 'free_courses', 'instructors', 'cvh_partners',
];
const LAST = 'blog';

function updateContent(int $landingComponentId, callable $mutate): void
{
    $row = DB::table('landing_component_translations')->where('landing_component_id', $landingComponentId)->where('locale', 'en')->first();
    $content = $mutate($row ? (json_decode($row->content, true) ?: []) : []);
    DB::table('landing_component_translations')->updateOrInsert(
        ['landing_component_id' => $landingComponentId, 'locale' => 'en'],
        ['content' => json_encode($content)]
    );
}

DB::transaction(function () {
    $landingId = DB::table('themes')->where('is_default', 1)->value('home_landing_id');
    if (!$landingId) {
        throw new RuntimeException('No default theme with a home landing page.');
    }

    $typeIds = DB::table('landing_builder_components')->pluck('id', 'name');
    if (!isset($typeIds['upcoming_courses'])) {
        $typeIds['upcoming_courses'] = DB::table('landing_builder_components')->insertGetId(['name' => 'upcoming_courses', 'category' => 'courses']);
    }
    $typeNames = $typeIds->flip();
    $spacerTypeId = $typeIds['vertical_spacer'];

    // Group the page into sections: each section is a component plus the spacers right after it.
    $groups = [];
    $rows = DB::table('landing_components')->where('landing_id', $landingId)->orderBy('order')->orderBy('id')->get();
    foreach ($rows as $row) {
        if ($row->component_id == $spacerTypeId and count($groups)) {
            $groups[count($groups) - 1]['spacers'][] = $row->id;
        } else {
            $groups[] = ['name' => $typeNames[$row->component_id] ?? '?', 'id' => $row->id, 'spacers' => []];
        }
    }

    // Upcoming Courses is new: add it with a spacer copied from an existing one.
    if (!collect($groups)->contains('name', 'upcoming_courses')) {
        $upcomingId = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $typeIds['upcoming_courses'], 'preview' => null, 'enable' => 1, 'order' => 0,
        ]);
        $spacerId = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $spacerTypeId, 'preview' => null, 'enable' => 1, 'order' => 0,
        ]);
        $spacerContent = DB::table('landing_component_translations as t')
            ->join('landing_components as c', 'c.id', '=', 't.landing_component_id')
            ->where('c.component_id', $spacerTypeId)->where('t.locale', 'en')->value('t.content');
        if ($spacerContent) {
            DB::table('landing_component_translations')->insert(['landing_component_id' => $spacerId, 'locale' => 'en', 'content' => $spacerContent]);
        }
        $groups[] = ['name' => 'upcoming_courses', 'id' => $upcomingId, 'spacers' => [$spacerId]];
    }

    // New order: the listed sections (switched on), the others as they were, the blog last.
    $listed = [];
    foreach (ORDER as $name) {
        foreach ($groups as $i => $group) {
            if ($group['name'] === $name) {
                $listed[] = $group;
                unset($groups[$i]);
                break;
            }
        }
    }
    $last = [];
    foreach ($groups as $i => $group) {
        if ($group['name'] === LAST) {
            $last[] = $group;
            unset($groups[$i]);
        }
    }

    $order = 1;
    foreach ([[$listed, true], [array_values($groups), false], [$last, null]] as [$sectionGroups, $switchOn]) {
        foreach ($sectionGroups as $group) {
            $update = ['order' => $order++];
            if ($switchOn) {
                $update['enable'] = 1;
            }
            DB::table('landing_components')->where('id', $group['id'])->update($update);
            foreach ($group['spacers'] as $spacerId) {
                $spacerUpdate = ['order' => $order++];
                if ($switchOn) {
                    $spacerUpdate['enable'] = 1;
                }
                DB::table('landing_components')->where('id', $spacerId)->update($spacerUpdate);
            }
        }
    }

    // Headings in the wording of the original demo, adapted to CVH.
    $idOf = fn(string $name) => DB::table('landing_components')->where('landing_id', $landingId)
        ->where('component_id', $typeIds[$name])->orderBy('order')->value('id');

    updateContent($idOf('trending_categories'), function ($c) {
        $c['main_content'] = array_merge($c['main_content'] ?? [], ['pre_title' => 'Trending', 'title' => 'Trending Categories']);
        return $c;
    });

    updateContent($idOf('best_selling_courses'), function ($c) {
        $c['main_content'] = array_merge($c['main_content'] ?? [], [
            'title' => 'Bestsellers Chosen by Our Students',
            'subtitle' => "The courses most learners in the CVH community enrol in: practical, expert-led learning on Africa's food systems.",
        ]);
        return $c;
    });

    updateContent($idOf('upcoming_courses'), function ($c) {
        $c['main_content'] = array_merge($c['main_content'] ?? [], [
            'pre_title' => 'Upcoming',
            'title' => 'Explore Upcoming Courses',
            'subtitle' => 'New food system courses launching soon. Follow a course to be told when it opens.',
            'button' => ['label' => 'View More', 'url' => '/upcoming_courses?sort=newest'],
        ]);
        return $c;
    });

    updateContent($idOf('instructors'), function ($c) {
        $c['main_content'] = array_merge($c['main_content'] ?? [], [
            'pre_title' => 'Instructors',
            'title' => 'Expert Instructors',
            'description' => 'Learn from experienced food system experts dedicated to delivering practical knowledge, guidance and real-world expertise',
        ]);
        return $c;
    });

    updateContent($idOf('cvh_partners'), function ($c) {
        $c['main_content'] = [
            'pre_title' => 'Organizations',
            'title' => 'Official Organizations',
            'description' => 'We collaborate with over 41 partners globally, from research institutes and universities to food system innovators, to develop, co-create and review our courses.',
        ];
        return $c;
    });

    // Report the page as it now stands.
    echo "Homepage (landing {$landingId}) now:\n";
    $rows = DB::table('landing_components')->where('landing_id', $landingId)->where('component_id', '!=', $spacerTypeId)->orderBy('order')->get();
    foreach ($rows as $row) {
        printf("  %3d  %-24s %s\n", $row->order, $typeNames[$row->component_id] ?? '?', $row->enable ? 'on' : 'off');
    }
    printf("Upcoming courses that will show: %d active. Courses with sales (for Bestsellers): %d.\n",
        DB::table('upcoming_courses')->where('status', 'active')->count(),
        DB::table('sales')->whereNotNull('webinar_id')->distinct()->count('webinar_id'));
});

echo "Homepage sections arranged like the original.\n";
