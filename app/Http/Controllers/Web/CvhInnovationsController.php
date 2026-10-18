<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: HD4A innovations showcase (/innovations), a custom page styled like the store listing.
 * Data lives in the cvh_innovators table (see database/cvh/08_innovations_showcase.php).
 */
class CvhInnovationsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('cohort');
        $country = $request->get('country');
        $search = trim((string)$request->get('search'));

        $base = DB::table('cvh_innovators')->where('enable', 1);

        $query = (clone $base)
            ->when($tab === 'winners', fn($q) => $q->where('is_winner', 1))
            ->when(in_array($tab, ['1', '2', '3'], true), fn($q) => $q->where('cohort', (int)$tab))
            ->when(!empty($country), fn($q) => $q->where('country', $country))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('business', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->orderBy('cohort')
            ->orderByDesc('is_winner')
            ->orderBy('sort_order');

        $tabs = [
            '' => ['title' => 'All Innovators', 'count' => (clone $base)->count()],
            'winners' => ['title' => 'Winners', 'count' => (clone $base)->where('is_winner', 1)->count()],
        ];
        foreach ([1, 2, 3] as $cohort) {
            $tabs[(string)$cohort] = ['title' => "Cohort {$cohort}", 'count' => (clone $base)->where('cohort', $cohort)->count()];
        }

        $data = [
            'pageTitle' => 'Innovations',
            'pageDescription' => 'Meet the agro-innovators of the HealthyDiets4Africa Agribusiness Accelerator Program, shaping Africa\'s food systems.',
            'pageRobot' => 'index, follow',
            'innovators' => $query->get(),
            'tabs' => $tabs,
            'countries' => (clone $base)->select('country')->distinct()->orderBy('country')->pluck('country'),
            'selectedTab' => (string)$tab,
            'selectedCountry' => $country,
            'search' => $search,
        ];

        return view('design_1.web.cvh_innovations.index', $data);
    }

    public function show($slug)
    {
        $innovator = DB::table('cvh_innovators')->where('enable', 1)->where('slug', $slug)->first();
        abort_if(empty($innovator), 404);

        $related = DB::table('cvh_innovators')->where('enable', 1)
            ->where('cohort', $innovator->cohort)->where('id', '!=', $innovator->id)
            ->orderByDesc('is_winner')->orderBy('sort_order')->limit(4)->get();

        $data = [
            'pageTitle' => $innovator->business . ' — ' . $innovator->name,
            'pageDescription' => $innovator->summary,
            'pageRobot' => 'index, follow',
            'pageMetaImage' => $innovator->photo,
            'innovator' => $innovator,
            'related' => $related,
        ];

        return view('design_1.web.cvh_innovations.show', $data);
    }
}
