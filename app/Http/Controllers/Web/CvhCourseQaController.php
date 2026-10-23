<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Webinar;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: in-house course Q&A (the template's Course Forum is part of the paid plugin).
 * Mirrors the original flow: enrolled learners and the course's instructors ask and answer questions;
 * instructors pin; the asker or an instructor marks an answer as the solution; owners edit their posts.
 * Tables: cvh_course_questions, cvh_course_answers (database/cvh/12_course_qa.php).
 */
class CvhCourseQaController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request, $slug)
    {
        [$course, $user] = $this->courseOrFail($slug);
        $search = trim((string)$request->get('search'));
        $filter = $request->get('filter');

        $base = DB::table('cvh_course_questions')->where('webinar_id', $course->id)->whereNull('deleted_at');

        $questions = (clone $base)
            ->when($filter === 'open', fn($q) => $q->whereNull('resolved_answer_id'))
            ->when($filter === 'resolved', fn($q) => $q->whereNotNull('resolved_answer_id'))
            ->when($filter === 'mine', fn($q) => $q->where('user_id', $user->id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")
                        ->orWhereIn('id', DB::table('cvh_course_answers')->whereNull('deleted_at')->where('body', 'like', "%{$search}%")->select('question_id'));
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc(DB::raw('greatest(created_at, coalesce(last_answer_at, 0))'))
            ->paginate(self::PER_PAGE)->withQueryString();

        $questionIds = (clone $base)->pluck('id');

        return view('design_1.web.cvh_course_qa.index', [
            'pageTitle' => 'Q&A — ' . $course->title,
            'pageRobot' => 'noindex, nofollow',
            'course' => $course,
            'questions' => $questions,
            'users' => $this->users($questions->getCollection()->pluck('user_id')),
            'search' => $search,
            'filter' => $filter,
            'isTeacher' => $this->isTeacher($course, $user),
            'stats' => [
                'questions' => $questionIds->count(),
                'resolved' => (clone $base)->whereNotNull('resolved_answer_id')->count(),
                'open' => (clone $base)->whereNull('resolved_answer_id')->count(),
                'answers' => DB::table('cvh_course_answers')->whereIn('question_id', $questionIds)->whereNull('deleted_at')->count(),
                'members' => DB::table('cvh_course_answers')->whereIn('question_id', $questionIds)->whereNull('deleted_at')->distinct()->count('user_id'),
            ],
        ]);
    }

    public function store(Request $request, $slug)
    {
        [$course, $user] = $this->courseOrFail($slug);
        $data = $request->validate(['title' => 'required|string|min:5|max:200', 'body' => 'required|string|min:5|max:10000']);

        $now = time();
        $id = DB::table('cvh_course_questions')->insertGetId([
            'webinar_id' => $course->id, 'user_id' => $user->id, 'title' => strip_tags($data['title']), 'body' => $data['body'],
            'created_at' => $now, 'updated_at' => $now,
        ]);

        if (!$this->isTeacher($course, $user)) {
            $this->notify('new_question_in_forum', ['[u.name]' => $user->full_name, '[c.title]' => $course->title],
                array_unique([$course->creator_id, $course->teacher_id]));
        }

        return redirect("/course/{$course->slug}/questions/{$id}")->with('cvh_qa_status', 'Your question has been posted.');
    }

    public function show($slug, $id)
    {
        [$course, $user] = $this->courseOrFail($slug);
        $question = $this->questionOrFail($course, $id);

        $answers = DB::table('cvh_course_answers')->where('question_id', $question->id)->whereNull('deleted_at')
            ->orderByRaw('id = ? desc', [(int)$question->resolved_answer_id])
            ->orderByDesc('is_pinned')->orderBy('created_at')
            ->paginate(self::PER_PAGE);

        return view('design_1.web.cvh_course_qa.show', [
            'pageTitle' => $question->title . ' — Q&A',
            'pageRobot' => 'noindex, nofollow',
            'course' => $course,
            'question' => $question,
            'answers' => $answers,
            'users' => $this->users(collect([$question->user_id])->merge($answers->getCollection()->pluck('user_id'))),
            'isTeacher' => $this->isTeacher($course, $user),
            'canResolve' => $this->isTeacher($course, $user) or $question->user_id == $user->id,
        ]);
    }

    public function answer(Request $request, $slug, $id)
    {
        [$course, $user] = $this->courseOrFail($slug);
        $question = $this->questionOrFail($course, $id);
        $data = $request->validate(['body' => 'required|string|min:2|max:10000']);

        $now = time();
        DB::table('cvh_course_answers')->insert(['question_id' => $question->id, 'user_id' => $user->id, 'body' => $data['body'], 'created_at' => $now, 'updated_at' => $now]);
        $this->refreshCounts($question->id, $now);

        if ($question->user_id != $user->id) {
            $this->notify('new_answer_in_forum', ['[u.name]' => $user->full_name, '[c.title]' => $course->title], [$question->user_id]);
        }

        return redirect("/course/{$course->slug}/questions/{$question->id}#answer-form")->with('cvh_qa_status', 'Your answer has been posted.');
    }

    /** Edit form for a question or an answer (owners only). */
    public function edit($slug, $type, $id)
    {
        [$course, $user] = $this->courseOrFail($slug);
        [$post, $question] = $this->ownPostOrFail($course, $user, $type, $id);

        return view('design_1.web.cvh_course_qa.edit', [
            'pageTitle' => 'Edit ' . ($type === 'question' ? 'question' : 'answer'),
            'pageRobot' => 'noindex, nofollow',
            'course' => $course, 'type' => $type, 'post' => $post, 'question' => $question,
        ]);
    }

    public function update(Request $request, $slug, $type, $id)
    {
        [$course, $user] = $this->courseOrFail($slug);
        [$post, $question] = $this->ownPostOrFail($course, $user, $type, $id);

        if ($type === 'question') {
            $data = $request->validate(['title' => 'required|string|min:5|max:200', 'body' => 'required|string|min:5|max:10000']);
            DB::table('cvh_course_questions')->where('id', $post->id)->update(['title' => strip_tags($data['title']), 'body' => $data['body'], 'updated_at' => time()]);
        } else {
            $data = $request->validate(['body' => 'required|string|min:2|max:10000']);
            DB::table('cvh_course_answers')->where('id', $post->id)->update(['body' => $data['body'], 'updated_at' => time()]);
        }

        return redirect("/course/{$course->slug}/questions/{$question->id}")->with('cvh_qa_status', 'Saved.');
    }

    /** pin / unpin / resolve / unresolve / delete for questions and answers. */
    public function action(Request $request, $slug)
    {
        [$course, $user] = $this->courseOrFail($slug);
        $data = $request->validate(['action' => 'required|in:pin_question,pin_answer,resolve,unresolve,delete_question,delete_answer', 'id' => 'required|integer']);
        $isTeacher = $this->isTeacher($course, $user);

        if (in_array($data['action'], ['pin_question', 'delete_question'])) {
            $question = $this->questionOrFail($course, $data['id']);
            abort_unless($isTeacher or ($data['action'] === 'delete_question' and $question->user_id == $user->id), 403);

            if ($data['action'] === 'pin_question') {
                DB::table('cvh_course_questions')->where('id', $question->id)->update(['is_pinned' => !$question->is_pinned]);
                return back()->with('cvh_qa_status', $question->is_pinned ? 'Question unpinned.' : 'Question pinned.');
            }

            DB::table('cvh_course_questions')->where('id', $question->id)->update(['deleted_at' => time()]);
            return redirect("/course/{$course->slug}/questions")->with('cvh_qa_status', 'Question deleted.');
        }

        $answer = DB::table('cvh_course_answers')->where('id', $data['id'])->whereNull('deleted_at')->first();
        abort_if(empty($answer), 404);
        $question = $this->questionOrFail($course, $answer->question_id);

        switch ($data['action']) {
            case 'pin_answer':
                abort_unless($isTeacher, 403);
                DB::table('cvh_course_answers')->where('id', $answer->id)->update(['is_pinned' => !$answer->is_pinned]);
                return back()->with('cvh_qa_status', $answer->is_pinned ? 'Answer unpinned.' : 'Answer pinned.');

            case 'resolve':
            case 'unresolve':
                abort_unless($isTeacher or $question->user_id == $user->id, 403);
                DB::table('cvh_course_questions')->where('id', $question->id)->update(['resolved_answer_id' => $data['action'] === 'resolve' ? $answer->id : null]);
                return back()->with('cvh_qa_status', $data['action'] === 'resolve' ? 'Marked as the solution.' : 'Solution removed.');

            case 'delete_answer':
                abort_unless($isTeacher or $answer->user_id == $user->id, 403);
                DB::table('cvh_course_answers')->where('id', $answer->id)->update(['deleted_at' => time()]);
                if ($question->resolved_answer_id == $answer->id) {
                    DB::table('cvh_course_questions')->where('id', $question->id)->update(['resolved_answer_id' => null]);
                }
                $this->refreshCounts($question->id);
                return back()->with('cvh_qa_status', 'Answer deleted.');
        }

        abort(400);
    }

    /* ---------- helpers ---------- */

    /** Course must exist, have Q&A on, and the user must be enrolled, an instructor of it, or an admin. */
    private function courseOrFail($slug): array
    {
        $user = auth()->user();
        $course = Webinar::query()->where('slug', $slug)->first();
        abort_if(empty($course) or empty($course->forum), 404);
        abort_unless($user->isAdmin() or $course->checkUserHasBought($user), 403, 'Enroll in this course to join its Q&A.');
        return [$course, $user];
    }

    private function questionOrFail($course, $id)
    {
        $question = DB::table('cvh_course_questions')->where('id', $id)->where('webinar_id', $course->id)->whereNull('deleted_at')->first();
        abort_if(empty($question), 404);
        return $question;
    }

    private function ownPostOrFail($course, $user, $type, $id): array
    {
        abort_unless(in_array($type, ['question', 'answer'], true), 404);
        if ($type === 'question') {
            $post = $this->questionOrFail($course, $id);
            $question = $post;
        } else {
            $post = DB::table('cvh_course_answers')->where('id', $id)->whereNull('deleted_at')->first();
            abort_if(empty($post), 404);
            $question = $this->questionOrFail($course, $post->question_id);
        }
        abort_unless($post->user_id == $user->id, 403);
        return [$post, $question];
    }

    private function isTeacher($course, $user): bool
    {
        if ($user->isAdmin() or $course->teacher_id == $user->id or $course->creator_id == $user->id) return true;
        return DB::table('webinar_partner_teacher')->where('webinar_id', $course->id)->where('teacher_id', $user->id)->exists();
    }

    private function refreshCounts(int $questionId, ?int $lastAnswerAt = null): void
    {
        $update = ['answers_count' => DB::table('cvh_course_answers')->where('question_id', $questionId)->whereNull('deleted_at')->count()];
        if ($lastAnswerAt) $update['last_answer_at'] = $lastAnswerAt;
        DB::table('cvh_course_questions')->where('id', $questionId)->update($update);
    }

    private function users($ids)
    {
        return User::query()->whereIn('id', collect($ids)->unique()->filter())->get()->keyBy('id');
    }

    /** Uses the platform's notification templates; a mail problem must never block posting. */
    private function notify(string $template, array $options, array $userIds): void
    {
        foreach (array_filter($userIds) as $userId) {
            try {
                sendNotification($template, $options, $userId);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
