{{-- CVH Africa: community forum home (categories + recent discussions). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
@endpush

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_community.header', [
            'title' => 'Community Forum',
            'subtitle' => 'Connect with learners, instructors and food system experts across Africa. Ask questions, share what you are working on and learn from each other.',
            'crumbs' => [['Community', null]],
            'action' => '<a href="/community/new" class="btn btn-primary btn-lg">Start a discussion</a>',
        ])

        <div class="container">
            <div class="d-flex flex-wrap gap-24 mt-24 font-14 text-gray-500">
                <span><strong class="text-dark">{{ $totals['topics'] }}</strong> {{ $totals['topics'] == 1 ? 'discussion' : 'discussions' }}</span>
                <span><strong class="text-dark">{{ $totals['replies'] }}</strong> {{ $totals['replies'] == 1 ? 'reply' : 'replies' }}</span>
                <span><strong class="text-dark">{{ $totals['members'] }}</strong> {{ $totals['members'] == 1 ? 'member' : 'members' }} posting</span>
            </div>

            <h2 class="font-20 text-dark mt-32">Categories</h2>
            <div class="row">
                @foreach($forumCategories as $category)
                    @php $stat = $stats[$category->id] ?? null; @endphp
                    <div class="col-12 col-md-6 col-lg-4 mt-16">
                        <a href="/community/{{ $category->slug }}" class="cvh-forum-cat text-decoration-none">
                            <div class="cvh-forum-cat__icon {{ $loop->even ? 'cvh-forum-cat__icon--alt' : '' }}">
                                @if($category->icon)
                                    @svg("iconsax-{$category->icon}", ['width' => '26px', 'height' => '26px'])
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-16 text-dark">{{ $category->title }}</h3>
                                <p class="font-13 text-gray-500 mt-4">{{ $category->description }}</p>
                                <div class="d-flex gap-12 mt-8">
                                    <span class="cvh-forum-meta"><x-iconsax-lin-messages-2 width="14px" height="14px"/> {{ $stat->topics ?? 0 }} {{ ($stat->topics ?? 0) == 1 ? 'discussion' : 'discussions' }}</span>
                                    <span class="cvh-forum-meta"><x-iconsax-lin-message width="14px" height="14px"/> {{ (int)($stat->replies ?? 0) }} {{ (int)($stat->replies ?? 0) == 1 ? 'reply' : 'replies' }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <h2 class="font-20 text-dark mt-40">Recent discussions</h2>
            <div class="cvh-forum-box mt-16">
                @forelse($recentTopics as $topic)
                    @include('design_1.web.cvh_community.topic_row', ['topic' => $topic, 'users' => $users, 'showCategory' => true])
                @empty
                    <div class="p-24 text-center text-gray-500">No discussions yet. <a href="/community/new" class="text-primary">Start the first one</a>.</div>
                @endforelse
            </div>
        </div>
    </main>
@endsection
