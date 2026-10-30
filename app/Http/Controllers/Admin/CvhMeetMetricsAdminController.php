<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Support\CvhGoogleMeet;
use App\Support\CvhMeetMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: engagement metrics for Google Meet live classes (Education > Live Class Metrics), from the attendance
 * that App\Support\CvhMeetMetrics fetches from Google every hour.
 */
class CvhMeetMetricsAdminController extends Controller
{
    private const PERIODS = ['30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months', 'all' => 'All time'];

    public function index(Request $request)
    {
        $this->authorize('admin_webinars_list');

        $courseId = $request->get('course_id');
        $period = array_key_exists((string)$request->get('period'), self::PERIODS) ? (string)$request->get('period') : '90';

        $sessions = Session::query()
            ->where('link', 'like', '%meet.google.com/%')
            ->where('date', '<=', time())
            ->when($period !== 'all', fn($q) => $q->where('date', '>=', time() - (int)$period * 86400))
            ->when($courseId, fn($q) => $q->where('webinar_id', $courseId))
            ->with('webinar')
            ->orderByDesc('date')
            ->get();

        $courseIds = Session::query()->where('link', 'like', '%meet.google.com/%')->distinct()->pluck('webinar_id');

        return view('admin.cvh_meet_metrics.index', [
            'pageTitle' => 'Live Class Metrics',
            'sessions' => $sessions,
            'metrics' => CvhMeetMetrics::summarise($sessions),
            'courses' => \App\Models\Webinar::query()->whereIn('id', $courseIds)->get()->mapWithKeys(fn($c) => [$c->id => $c->title]),
            'periods' => self::PERIODS,
            'period' => $period,
            'courseId' => $courseId,
            'connected' => CvhGoogleMeet::connected(),
            'ready' => CvhMeetMetrics::ready(),
        ]);
    }

    public function show($id)
    {
        $this->authorize('admin_webinars_list');

        $session = Session::query()->with('webinar')->findOrFail($id);
        $metrics = CvhMeetMetrics::summarise([$session]);
        $course = $session->webinar;

        $attendance = DB::table('cvh_meet_attendance')->where('session_id', $session->id)->orderBy('first_join')->get();
        $users = \App\User::query()->whereIn('id', $attendance->pluck('user_id')->filter()->unique())->get()->keyBy('id');
        $teacherIds = $course ? collect([$course->teacher_id])->merge(DB::table('webinar_partner_teacher')->where('webinar_id', $course->id)->pluck('teacher_id'))->filter()->all() : [];

        // enrolled learners who did not join any meeting of this session
        $attendedIds = $attendance->pluck('user_id')->filter()->all();
        $absent = $course ? \App\User::query()
            ->whereIn('id', DB::table('sales')->where('webinar_id', $course->id)->whereNull('refund_at')->pluck('buyer_id'))
            ->whereNotIn('id', $attendedIds)
            ->orderBy('full_name')->get(['id', 'full_name', 'email']) : collect();

        return view('admin.cvh_meet_metrics.show', [
            'pageTitle' => 'Live Class Metrics: ' . $session->title,
            'session' => $session,
            'row' => $metrics['rows'][$session->id],
            'attendance' => $attendance,
            'users' => $users,
            'teacherIds' => $teacherIds,
            'absent' => $absent,
            'conferences' => DB::table('cvh_meet_conferences')->where('session_id', $session->id)->orderBy('started_at')->get(),
        ]);
    }

    public function sync($id)
    {
        $this->authorize('admin_webinars_list');

        $session = Session::query()->findOrFail($id);
        try {
            $found = CvhMeetMetrics::syncSession($session);
            $message = $found ? "Attendance updated from Google ({$found} meeting" . ($found > 1 ? 's' : '') . ').' : 'Google has no meeting for this session yet: nobody has joined it, or it is older than 30 days.';
            return back()->with('cvh_meet_metrics_status', $message);
        } catch (\Throwable $exception) {
            return back()->with('cvh_meet_metrics_error', $exception->getMessage());
        }
    }
}
