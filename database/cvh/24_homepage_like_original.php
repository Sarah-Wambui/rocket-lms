<?php
/**
 * CVH Africa — homepage sections in the order and style of the original Rocket LMS demo (lms.rocket-soft.org):
 *   Hero, Statistics, Features, Featured Courses, Trending Categories, Bestsellers, Top Rated, the scrolling
 *   bands of all course categories, Upcoming Courses, Free Courses, Expert Instructors, Official Organizations
 *   (all partner logos in one panel), then any other sections already on the page, then the Blog.
 * Sections the page does not have yet (Features, category bands, Upcoming) are added.
 * Each section keeps the spacer that follows it. Sections not in that list keep their on/off state.
 * Run from the project root:  php database/cvh/24_homepage_like_original.php   (re-runnable)
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const ORDER = [
    'two_columns_hero', 'statistics', 'features_4x', 'featured_courses', 'trending_categories', 'best_selling_courses',
    'best_rated_courses', 'links_and_titles_slider_2_rows', 'upcoming_courses', 'free_courses', 'instructors', 'cvh_partners',
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
    foreach (['features_4x' => 'information', 'links_and_titles_slider_2_rows' => 'miscellaneous', 'upcoming_courses' => 'courses'] as $name => $category) {
        if (!isset($typeIds[$name])) {
            $typeIds[$name] = DB::table('landing_builder_components')->insertGetId(['name' => $name, 'category' => $category]);
        }
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

    // Sections the page does not have yet: add each with a spacer copied from an existing one.
    foreach (ORDER as $name) {
        if (collect($groups)->contains('name', $name) or !isset($typeIds[$name])) {
            continue;
        }
        $newId = DB::table('landing_components')->insertGetId([
            'landing_id' => $landingId, 'component_id' => $typeIds[$name], 'preview' => null, 'enable' => 1, 'order' => 0,
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
        $groups[] = ['name' => $name, 'id' => $newId, 'spacers' => [$spacerId]];
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

    updateContent($idOf('features_4x'), function ($c) {
        $c['main_content'] = [
            'pre_title' => 'Features',
            'title' => 'Master New Skills Using Advanced Learning Features',
            'description' => "Everything you need to learn about Africa's food systems at your own pace, with expert guidance and a certificate when you finish.",
            'button' => ['label' => 'Explore Courses', 'icon' => 'bul-video-horizontal', 'url' => '/classes'],
        ];
        $c['checked_items'] = [
            'cvhF1' => 'Flexible Learning Schedule',
            'cvhF2' => 'Free Courses for Everyone',
            'cvhF3' => 'Expert Instructor Access',
            'cvhF4' => 'Self-Paced Progression',
        ];
        $c['features_cards'] = [
            'cvhC1' => ['title' => 'Smart Quizzes', 'description' => 'Check what you have learnt with quizzes at the end of each module.', 'icon' => 'bul-task-square', 'url' => ''],
            'cvhC2' => ['title' => 'Verified Course Certificates', 'description' => 'Earn a certificate anyone can verify online when you complete a course.', 'icon' => 'bul-medal-star', 'url' => '/certificates/verify'],
            'cvhC3' => ['title' => 'Live Classes', 'description' => 'Join live sessions with food system experts and ask your questions.', 'icon' => 'bul-calendar-tick', 'url' => ''],
            'cvhC4' => ['title' => 'Community Support', 'description' => 'Learn with peers and get answers in the course Q&A and the community forum.', 'icon' => 'bul-message-question', 'url' => '/community'],
        ];
        return $c;
    });

    // The category bands list every enabled course category when they have no items of their own.
    updateContent($idOf('links_and_titles_slider_2_rows'), function ($c) {
        $c['title_items'] = [];
        $c['card_style'] = $c['card_style'] ?? 'rotate';
        return $c;
    });

    updateContent($idOf('best_rated_courses'), function ($c) {
        $c['main_content'] = array_merge($c['main_content'] ?? [], ['title' => 'Top Rated by Our Students']);
        return $c;
    });

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
        printf("  %3d  %-32s %s\n", $row->order, $typeIds->flip()[$row->component_id] ?? '?', $row->enable ? 'on' : 'off');
    }
    printf("Upcoming courses that will show: %d active. Courses with sales (for Bestsellers): %d. Courses with reviews (for Top Rated): %d.\n",
        DB::table('upcoming_courses')->where('status', 'active')->count(),
        DB::table('sales')->whereNotNull('webinar_id')->distinct()->count('webinar_id'),
        DB::table('webinar_reviews')->where('status', 'active')->distinct()->count('webinar_id'));
});

echo "Homepage sections arranged like the original.\n";
