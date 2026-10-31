<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\Sale;
use App\Models\Session;
use App\Models\Webinar;
use App\Models\WebinarPartnerTeacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MyCoursesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize("panel_webinars_lists");

        $user = auth()->user();

        if ($user->isUser()) {
            abort(404);
        }

        $query = Webinar::query()->where(function ($query) use ($user) {
            $query->where('creator_id', $user->id);
            $query->orWhere('teacher_id', $user->id);
        });

        return $this->getViewDataByQuery($request, $query, $user);
    }


    public function invitations(Request $request)
    {
        $this->authorize("panel_webinars_invited_lists");

        $user = auth()->user();

        if ($user->isUser()) {
            abort(404);
        }

        $invitedWebinarIds = WebinarPartnerTeacher::where('teacher_id', $user->id)->pluck('webinar_id')->toArray();
        $query = Webinar::query();
        $query->whereIn('id', $invitedWebinarIds);

        return $this->getViewDataByQuery($request, $query, $user, true);
    }

    private function getViewDataByQuery(Request $request, Builder $query, $user, $isInvitedCoursesPage = false)
    {
        $pageListData = $this->getMyCoursesListPageData($request, $query);

        if ($request->ajax()) {
            return $pageListData;
        }

        $topStats = $this->handleMyCoursesListTopStats($user);

        $upcomingLiveSessions = null;
        if (!$isInvitedCoursesPage) {
            $upcomingLiveSessions = $this->getMyCoursesListUpcomingLiveSessions($user);
        }

        $pageTitle = $isInvitedCoursesPage ? trans('panel.invited_classes') : trans('update.my_courses');
        $breadcrumbs = [
            ['text' => trans('update.platform'), 'url' => '/'],
            ['text' => trans('panel.dashboard'), 'url' => '/panel'],
            ['text' => $pageTitle, 'url' => null],
        ];

        $data = [
            'pageTitle' => $pageTitle,
            'breadcrumbs' => $breadcrumbs,
            'upcomingLiveSessions' => $upcomingLiveSessions,
            'isInvitedCoursesPage' => $isInvitedCoursesPage,
            ...$topStats,
            ...$pageListData,
        ];

        return view('design_1.panel.webinars.my_courses.index', $data);
    }

    private function handleMyCoursesListTopStats($user): array
    {
        $query = Webinar::query()->where(function ($query) use ($user) {
            $query->where('creator_id', $user->id);
            $query->orWhere('teacher_id', $user->id);
        });

        $totalCoursesCount = deepClone($query)->count();

        $totalCoursesHours = deepClone($query)->sum('duration');

        $totalLiveCoursesSalesAmount = 0;
        $totalCoursesSalesAmount = 0;

        // CVH: summed in the database instead of loading every sale with its course (ran out of memory with the imported sales)
        $webinarSales = Sale::query()->toBase()
            ->leftJoin('webinars', 'webinars.id', '=', 'sales.webinar_id')
            ->where('sales.seller_id', $user->id)
            ->where('sales.type', 'webinar')
            ->whereNotNull('sales.webinar_id')
            ->whereNull('sales.refund_at')
            ->selectRaw("coalesce(sum(sales.amount), 0) as total, coalesce(sum(case when webinars.type = 'webinar' then sales.amount else 0 end), 0) as live")
            ->first();

        $totalCoursesSalesAmount = (float)$webinarSales->total;
        $totalLiveCoursesSalesAmount = (float)$webinarSales->live;

        return [
            'totalCoursesCount' => $totalCoursesCount,
            'totalCoursesHours' => $totalCoursesHours,
            'totalLiveCoursesSalesAmount' => $totalLiveCoursesSalesAmount,
            'totalCoursesSalesAmount' => $totalCoursesSalesAmount,
        ];
    }

    private function handleFilters(Request $request, Builder $query): Builder
    {

        return $query;
    }

    public function getMyCoursesListPageData(Request $request, Builder $query)
    {
        $page = $request->get('page') ?? 1;
        $count = $this->perPage;

        $total = $query->count();

        $query->limit($count);
        $query->offset(($page - 1) * $count);

        $courses = $query->with([
            'reviews' => function ($query) {
                $query->where('status', 'active');
            },
            'category',
            'teacher'
        ])
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($courses as $course) {
            $giftsIds = Gift::query()->where('webinar_id', $course->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('date');
                    $query->orWhere('date', '<', time());
                })
                ->whereHas('sale')
                ->pluck('id')
                ->toArray();

            $sales = Sale::query()
                ->where(function ($query) use ($course, $giftsIds) {
                    $query->where('webinar_id', $course->id);
                    $query->orWhereIn('gift_id', $giftsIds);
                })
                ->whereNull('refund_at')
                ->toBase()->get(['id', 'amount']); // CVH: only what the cards show (count and amount), not full Sale models

            $course->sales = $sales;
        }

        if ($request->ajax()) {
            return $this->handleAjaxResponse($request, $courses, $total, $count);
        }

        return [
            'courses' => $courses,
            'pagination' => $this->makePagination($request, $courses, $total, $count, true),
        ];
    }

    private function handleAjaxResponse(Request $request, $courses, $total, $count)
    {
        $html = "";

        foreach ($courses as $courseItem) {
            $html .= '<div class="col-12 col-lg-6 mb-32">';
            $html .= (string)view()->make("design_1.panel.webinars.my_courses.course_card.index", ['course' => $courseItem]);
            $html .= '</div>';
        }

        return response()->json([
            'data' => $html,
            'pagination' => $this->makePagination($request, $courses, $total, $count, true)
        ]);
    }

    private function getMyCoursesListUpcomingLiveSessions($user)
    {
        // CVH: only this instructor's own courses' sessions (the template listed every upcoming session on the site)
        return Session::whereNotNull('webinar_id')
            ->whereHas('webinar', function ($query) use ($user) {
                $query->where('creator_id', $user->id)
                    ->orWhere('teacher_id', $user->id);
            })
            ->with('webinar')
            ->limit(10)
            ->where('date', '>=', time())
            ->orderBy('date', 'asc')
            ->where('status', Session::$Active)
            ->whereDoesntHave('agoraHistory', function ($query) {
                $query->whereNotNull('end_at');
            })
            ->get();
    }
}
