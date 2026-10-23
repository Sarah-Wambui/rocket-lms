<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: admin overview of the in-house course Q&A (Education > Course Forum), replacing the
 * template's paid Course Forum admin page. Lists questions across all courses; admins can open or delete them.
 */
class CvhCourseQaAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('admin_course_question_forum_list');

        $search = trim((string)$request->get('search'));
        $courseId = $request->get('course_id');
        $status = $request->get('status');

        $questions = DB::table('cvh_course_questions as q')
            ->join('webinars as w', 'w.id', '=', 'q.webinar_id')
            ->whereNull('q.deleted_at')
            ->when($courseId, fn($q) => $q->where('q.webinar_id', $courseId))
            ->when($status === 'open', fn($q) => $q->whereNull('q.resolved_answer_id'))
            ->when($status === 'resolved', fn($q) => $q->whereNotNull('q.resolved_answer_id'))
            ->when($search !== '', fn($q) => $q->where(fn($q) => $q->where('q.title', 'like', "%{$search}%")->orWhere('q.body', 'like', "%{$search}%")))
            ->orderByDesc(DB::raw('greatest(q.created_at, coalesce(q.last_answer_at, 0))'))
            ->select('q.*', 'w.slug as course_slug')
            ->paginate(20)->withQueryString();

        $courseIds = DB::table('cvh_course_questions')->whereNull('deleted_at')->distinct()->pluck('webinar_id');
        $courses = \App\Models\Webinar::query()->whereIn('id', $courseIds)->get()->mapWithKeys(fn($c) => [$c->id => $c->title]);

        return view('admin.cvh_course_qa.index', [
            'pageTitle' => 'Course Forum (Q&A)',
            'questions' => $questions,
            'users' => User::query()->whereIn('id', $questions->getCollection()->pluck('user_id')->unique())->get()->keyBy('id'),
            'courses' => $courses,
            'search' => $search,
            'courseId' => $courseId,
            'status' => $status,
            'stats' => [
                'questions' => DB::table('cvh_course_questions')->whereNull('deleted_at')->count(),
                'open' => DB::table('cvh_course_questions')->whereNull('deleted_at')->whereNull('resolved_answer_id')->count(),
                'answers' => DB::table('cvh_course_answers')->whereNull('deleted_at')->count(),
            ],
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('admin_course_question_forum_list');
        DB::table('cvh_course_questions')->where('id', $id)->update(['deleted_at' => time()]);

        return back()->with('cvh_qa_admin_status', 'Question deleted.');
    }
}
