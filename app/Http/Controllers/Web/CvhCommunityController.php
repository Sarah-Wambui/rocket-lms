<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CVH Africa: in-house community forum (/community). Our own tables (cvh_forum_*), see database/cvh/09_community_forum.php.
 * Reading is public; posting needs a logged-in account; admins can pin, lock and delete.
 */
class CvhCommunityController extends Controller
{
    private const PER_PAGE = 20;

    public function index()
    {
        $categories = DB::table('cvh_forum_categories')->where('enable', 1)->orderBy('sort_order')->get();

        $stats = DB::table('cvh_forum_topics')->whereNull('deleted_at')
            ->selectRaw('category_id, count(*) as topics, sum(replies_count) as replies, max(greatest(created_at, coalesce(last_reply_at, 0))) as last_activity')
            ->groupBy('category_id')->get()->keyBy('category_id');

        $recent = $this->topicsQuery()->orderByDesc(DB::raw('greatest(t.created_at, coalesce(t.last_reply_at, 0))'))->limit(6)->get();

        return view('design_1.web.cvh_community.index', [
            'pageTitle' => 'Community Forum',
            'pageDescription' => 'Connect with learners, instructors and food system experts across Africa on the CVH community forum.',
            'pageRobot' => 'index, follow',
            'forumCategories' => $categories,
            'stats' => $stats,
            'recentTopics' => $recent,
            'users' => $this->usersFor($recent),
            'totals' => [
                'topics' => DB::table('cvh_forum_topics')->whereNull('deleted_at')->count(),
                'replies' => DB::table('cvh_forum_replies')->whereNull('deleted_at')->count(),
                'members' => DB::table('cvh_forum_topics')->whereNull('deleted_at')->distinct()->count('user_id'),
            ],
        ]);
    }

    public function category(Request $request, $slug)
    {
        $category = DB::table('cvh_forum_categories')->where('enable', 1)->where('slug', $slug)->first();
        abort_if(empty($category), 404);

        $search = trim((string)$request->get('search'));
        $topics = $this->topicsQuery()
            ->where('t.category_id', $category->id)
            ->when($search !== '', fn($q) => $q->where(fn($q) => $q->where('t.title', 'like', "%{$search}%")->orWhere('t.body', 'like', "%{$search}%")))
            ->orderByDesc('t.is_pinned')
            ->orderByDesc(DB::raw('greatest(t.created_at, coalesce(t.last_reply_at, 0))'))
            ->paginate(self::PER_PAGE)->withQueryString();

        return view('design_1.web.cvh_community.category', [
            'pageTitle' => $category->title . ' — Community Forum',
            'pageDescription' => $category->description,
            'pageRobot' => 'index, follow',
            'category' => $category,
            'topics' => $topics,
            'users' => $this->usersFor($topics->getCollection()),
            'search' => $search,
        ]);
    }

    public function topic($id)
    {
        $topic = $this->topicsQuery()->where('t.id', $id)->first();
        abort_if(empty($topic), 404);

        DB::table('cvh_forum_topics')->where('id', $topic->id)->increment('views');

        $replies = DB::table('cvh_forum_replies')->where('topic_id', $topic->id)->whereNull('deleted_at')
            ->orderBy('created_at')->paginate(self::PER_PAGE);

        $userIds = collect([$topic->user_id])->merge($replies->getCollection()->pluck('user_id'));

        return view('design_1.web.cvh_community.topic', [
            'pageTitle' => $topic->title . ' — Community Forum',
            'pageDescription' => \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $topic->body), 155),
            'pageRobot' => 'index, follow',
            'topic' => $topic,
            'replies' => $replies,
            'users' => User::query()->whereIn('id', $userIds->unique())->get()->keyBy('id'),
            'isModerator' => $this->isModerator(),
        ]);
    }

    public function create(Request $request)
    {
        return view('design_1.web.cvh_community.create', [
            'pageTitle' => 'Start a discussion — Community Forum',
            'pageRobot' => 'noindex, nofollow',
            'forumCategories' => DB::table('cvh_forum_categories')->where('enable', 1)->orderBy('sort_order')->get(),
            'selectedCategory' => $request->get('category'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:cvh_forum_categories,id',
            'title' => 'required|string|min:5|max:200',
            'body' => 'required|string|min:10|max:10000',
        ]);

        $now = time();
        $id = DB::table('cvh_forum_topics')->insertGetId([
            'category_id' => $data['category_id'],
            'user_id' => auth()->id(),
            'title' => strip_tags($data['title']),
            'body' => $data['body'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect("/community/topic/{$id}")->with('cvh_forum_status', 'Your discussion has been posted.');
    }

    public function reply(Request $request, $id)
    {
        $topic = DB::table('cvh_forum_topics')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(empty($topic), 404);

        if ($topic->is_locked and !$this->isModerator()) {
            return back()->with('cvh_forum_status', 'This discussion is locked; new replies are closed.');
        }

        $data = $request->validate(['body' => 'required|string|min:2|max:10000']);

        $now = time();
        DB::table('cvh_forum_replies')->insert([
            'topic_id' => $topic->id, 'user_id' => auth()->id(), 'body' => $data['body'], 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('cvh_forum_topics')->where('id', $topic->id)->update([
            'replies_count' => DB::table('cvh_forum_replies')->where('topic_id', $topic->id)->whereNull('deleted_at')->count(),
            'last_reply_at' => $now,
            'last_reply_user_id' => auth()->id(),
        ]);

        $lastPage = (int)ceil(DB::table('cvh_forum_replies')->where('topic_id', $topic->id)->whereNull('deleted_at')->count() / self::PER_PAGE);

        return redirect("/community/topic/{$topic->id}?page=" . max(1, $lastPage) . '#reply-form')->with('cvh_forum_status', 'Your reply has been posted.');
    }

    public function moderate(Request $request)
    {
        abort_unless($this->isModerator(), 403);

        $data = $request->validate([
            'action' => 'required|in:pin,unpin,lock,unlock,delete_topic,delete_reply',
            'id' => 'required|integer',
        ]);

        switch ($data['action']) {
            case 'pin':
            case 'unpin':
                DB::table('cvh_forum_topics')->where('id', $data['id'])->update(['is_pinned' => $data['action'] === 'pin']);
                break;
            case 'lock':
            case 'unlock':
                DB::table('cvh_forum_topics')->where('id', $data['id'])->update(['is_locked' => $data['action'] === 'lock']);
                break;
            case 'delete_topic':
                $categorySlug = DB::table('cvh_forum_topics as t')->join('cvh_forum_categories as c', 'c.id', '=', 't.category_id')->where('t.id', $data['id'])->value('c.slug');
                DB::table('cvh_forum_topics')->where('id', $data['id'])->update(['deleted_at' => time()]);
                return redirect('/community/' . $categorySlug)->with('cvh_forum_status', 'Discussion deleted.');
            case 'delete_reply':
                $topicId = DB::table('cvh_forum_replies')->where('id', $data['id'])->value('topic_id');
                DB::table('cvh_forum_replies')->where('id', $data['id'])->update(['deleted_at' => time()]);
                DB::table('cvh_forum_topics')->where('id', $topicId)->update([
                    'replies_count' => DB::table('cvh_forum_replies')->where('topic_id', $topicId)->whereNull('deleted_at')->count(),
                ]);
                break;
        }

        return back()->with('cvh_forum_status', 'Done.');
    }

    private function topicsQuery()
    {
        return DB::table('cvh_forum_topics as t')
            ->join('cvh_forum_categories as c', 'c.id', '=', 't.category_id')
            ->whereNull('t.deleted_at')->where('c.enable', 1)
            ->select('t.*', 'c.slug as category_slug', 'c.title as category_title');
    }

    private function usersFor($topics)
    {
        $ids = collect($topics)->flatMap(fn($t) => [$t->user_id, $t->last_reply_user_id])->filter()->unique();
        return User::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    private function isModerator(): bool
    {
        return auth()->check() and auth()->user()->isAdmin();
    }
}
