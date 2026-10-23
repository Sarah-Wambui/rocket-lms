{{-- CVH Africa: course Q&A — list of questions + ask form --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
    <style>
        .cvh-qa-stat { flex: 1 1 120px; background: var(--white); border: 1px solid var(--gray-200); border-radius: 16px; padding: 14px 16px; }
        .cvh-qa-stat strong { display: block; font-size: 22px; color: var(--dark); }
        .cvh-forum-tag--resolved { background: rgba(94, 172, 73, .15); color: var(--accent); }
        .cvh-forum-tag--open { background: rgba(255, 162, 0, .15); color: #c27c00; }
    </style>
@endpush

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_course_qa.header', [
            'title' => 'Course Q&A',
            'action' => '<a href="#ask" class="btn btn-primary btn-lg">Ask a question</a>',
        ])

        <div class="container">
            <div class="d-flex flex-wrap gap-12 mt-24">
                <div class="cvh-qa-stat"><strong>{{ $stats['questions'] }}</strong><span class="font-12 text-gray-500">Questions</span></div>
                <div class="cvh-qa-stat"><strong>{{ $stats['resolved'] }}</strong><span class="font-12 text-gray-500">Resolved</span></div>
                <div class="cvh-qa-stat"><strong>{{ $stats['open'] }}</strong><span class="font-12 text-gray-500">Open</span></div>
                <div class="cvh-qa-stat"><strong>{{ $stats['answers'] }}</strong><span class="font-12 text-gray-500">Answers</span></div>
                <div class="cvh-qa-stat"><strong>{{ $stats['members'] }}</strong><span class="font-12 text-gray-500">Active members</span></div>
            </div>

            <form action="/course/{{ $course->slug }}/questions" method="get" class="d-flex flex-wrap align-items-center gap-8 mt-24">
                @foreach(['' => 'All', 'open' => 'Open', 'resolved' => 'Resolved', 'mine' => 'My questions'] as $key => $label)
                    <a href="/course/{{ $course->slug }}/questions?{{ http_build_query(array_filter(['filter' => $key, 'search' => $search])) }}"
                       class="cvh-forum-tag {{ (string)$filter === (string)$key ? 'cvh-forum-tag--admin' : 'cvh-forum-tag--cat' }}" style="padding: 6px 12px; font-size: 13px;">{{ $label }}</a>
                @endforeach
                @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Search questions and answers" class="form-control flex-grow-1" style="min-width: 200px; max-width: 420px;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <div class="cvh-forum-box mt-16">
                @forelse($questions as $q)
                    @php $author = $users[$q->user_id] ?? null; @endphp
                    <div class="cvh-forum-row">
                        <img src="{{ $author ? $author->getAvatar(48) : getDefaultAvatarPath() }}" alt="" class="cvh-forum-avatar">
                        <div class="cvh-forum-row__main">
                            <div class="d-flex flex-wrap gap-6">
                                @if($q->is_pinned)<span class="cvh-forum-tag cvh-forum-tag--pinned"><x-iconsax-bol-star-1 width="12px" height="12px"/> Pinned</span>@endif
                                @if($q->resolved_answer_id)
                                    <span class="cvh-forum-tag cvh-forum-tag--resolved"><x-iconsax-bol-tick-circle width="12px" height="12px"/> Resolved</span>
                                @else
                                    <span class="cvh-forum-tag cvh-forum-tag--open">Open</span>
                                @endif
                            </div>
                            <a href="/course/{{ $course->slug }}/questions/{{ $q->id }}" class="cvh-forum-row__title mt-4">{{ $q->title }}</a>
                            <div class="font-12 text-gray-500 mt-4">{{ $author->full_name ?? 'Former member' }} · {{ dateTimeFormat($q->created_at, 'j M Y', false) }}</div>
                        </div>
                        <div class="cvh-forum-row__stats">
                            <div class="cvh-forum-meta justify-content-end"><x-iconsax-lin-message width="14px" height="14px"/> {{ $q->answers_count }} {{ $q->answers_count == 1 ? 'answer' : 'answers' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-24 text-center text-gray-500">
                        {{ $search !== '' || $filter ? 'No questions match.' : 'No questions yet — be the first to ask.' }}
                    </div>
                @endforelse
            </div>

            @if($questions->hasPages())
                <div class="mt-24">{{ $questions->links('pagination::bootstrap-4') }}</div>
            @endif

            <form action="/course/{{ $course->slug }}/questions" method="post" class="cvh-forum-box p-24 mt-32" id="ask">
                @csrf
                <h2 class="font-18 text-dark">Ask a question</h2>
                <p class="font-13 text-gray-500 mt-4">Your classmates and the course instructor will be able to answer.</p>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="200" class="form-control mt-12 @error('title') is-invalid @enderror" placeholder="Summarise your question" required>
                @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <textarea name="body" rows="5" maxlength="10000" class="form-control mt-12 @error('body') is-invalid @enderror" placeholder="Add details: which lesson, what you tried, what is unclear..." required>{{ old('body') }}</textarea>
                @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-primary btn-lg mt-12">Post question</button>
            </form>
        </div>
    </main>
@endsection
