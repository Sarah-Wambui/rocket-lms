{{-- CVH Africa: course Q&A — one question with answers --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
    <style>
        .cvh-forum-tag--resolved { background: rgba(94, 172, 73, .15); color: var(--accent); }
        .cvh-forum-tag--open { background: rgba(255, 162, 0, .15); color: #c27c00; }
        .cvh-forum-post.is-solution { background: rgba(94, 172, 73, .07); border-left: 4px solid var(--accent); }
    </style>
@endpush

@php
    $act = function ($action, $id, $label, $icon, $danger = false) use ($course) {
        $confirm = $danger ? ' onsubmit="return confirm(\'Delete this permanently?\')"' : '';
        return '<form action="/course/' . e($course->slug) . '/qa/action" method="post" class="cvh-forum-mod"' . $confirm . '>' . csrf_field()
            . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . (int)$id . '">'
            . '<button type="submit"' . ($danger ? ' class="is-danger"' : '') . '>' . svg("iconsax-{$icon}", ['width' => '14px', 'height' => '14px'])->toHtml() . ' ' . e($label) . '</button></form>';
    };
    $byline = function ($user, $ts) use ($course) {
        $role = '';
        if ($user and ($user->id == $course->teacher_id or $user->id == $course->creator_id)) $role = ' <span class="cvh-forum-tag cvh-forum-tag--admin">Instructor</span>';
        elseif ($user and $user->role_name === 'admin') $role = ' <span class="cvh-forum-tag cvh-forum-tag--admin">CVH Team</span>';
        return '<div class="font-14 font-weight-bold text-dark">' . e($user->full_name ?? 'Former member') . $role . '</div>'
            . '<div class="font-12 text-gray-500">' . e(dateTimeFormat($ts, 'j M Y, H:i', false)) . '</div>';
    };
    $me = auth()->id();
    $asker = $users[$question->user_id] ?? null;
@endphp

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_course_qa.header', ['title' => $question->title, 'crumbs' => [['Question', null]]])

        <div class="container">
            <div class="d-flex flex-wrap align-items-center gap-8 mt-24">
                @if($question->is_pinned)<span class="cvh-forum-tag cvh-forum-tag--pinned"><x-iconsax-bol-star-1 width="12px" height="12px"/> Pinned</span>@endif
                @if($question->resolved_answer_id)
                    <span class="cvh-forum-tag cvh-forum-tag--resolved"><x-iconsax-bol-tick-circle width="12px" height="12px"/> Resolved</span>
                @else
                    <span class="cvh-forum-tag cvh-forum-tag--open">Open</span>
                @endif
                <span class="cvh-forum-meta ml-auto">{{ $question->answers_count }} {{ $question->answers_count == 1 ? 'answer' : 'answers' }}</span>
            </div>

            <div class="cvh-forum-box mt-16">
                <div class="cvh-forum-post">
                    <div class="d-flex align-items-center gap-12">
                        <img src="{{ $asker ? $asker->getAvatar(52) : getDefaultAvatarPath() }}" alt="" class="cvh-forum-avatar cvh-forum-avatar--lg">
                        <div>{!! $byline($asker, $question->created_at) !!}</div>
                    </div>
                    <div class="cvh-forum-post__body mt-16">{!! nl2br(e($question->body)) !!}</div>
                    <div class="d-flex flex-wrap gap-4 mt-16">
                        @if($isTeacher){!! $act('pin_question', $question->id, $question->is_pinned ? 'Unpin' : 'Pin', 'bol-star-1') !!}@endif
                        @if($question->user_id == $me)
                            <form action="/course/{{ $course->slug }}/qa/question/{{ $question->id }}/edit" method="get" class="cvh-forum-mod"><button type="submit"><x-iconsax-lin-edit-2 width="14px" height="14px"/> Edit</button></form>
                        @endif
                        @if($isTeacher or $question->user_id == $me){!! $act('delete_question', $question->id, 'Delete question', 'lin-trash', true) !!}@endif
                    </div>
                </div>

                @foreach($answers as $a)
                    @php $author = $users[$a->user_id] ?? null; $isSolution = $question->resolved_answer_id == $a->id; @endphp
                    <div class="cvh-forum-post {{ $isSolution ? 'is-solution' : '' }}" id="answer-{{ $a->id }}">
                        <div class="d-flex align-items-center gap-12">
                            <img src="{{ $author ? $author->getAvatar(40) : getDefaultAvatarPath() }}" alt="" class="cvh-forum-avatar">
                            <div class="flex-grow-1">{!! $byline($author, $a->created_at) !!}</div>
                            @if($isSolution)<span class="cvh-forum-tag cvh-forum-tag--resolved"><x-iconsax-bol-tick-circle width="12px" height="12px"/> Solution</span>@endif
                            @if($a->is_pinned)<span class="cvh-forum-tag cvh-forum-tag--pinned"><x-iconsax-bol-star-1 width="12px" height="12px"/> Pinned</span>@endif
                        </div>
                        <div class="cvh-forum-post__body mt-12">{!! nl2br(e($a->body)) !!}</div>
                        <div class="d-flex flex-wrap gap-4 mt-8">
                            @if($canResolve){!! $isSolution ? $act('unresolve', $a->id, 'Unmark solution', 'lin-close-circle') : $act('resolve', $a->id, 'Mark as solution', 'lin-tick-circle') !!}@endif
                            @if($isTeacher){!! $act('pin_answer', $a->id, $a->is_pinned ? 'Unpin' : 'Pin', 'bol-star-1') !!}@endif
                            @if($a->user_id == $me)
                                <form action="/course/{{ $course->slug }}/qa/answer/{{ $a->id }}/edit" method="get" class="cvh-forum-mod"><button type="submit"><x-iconsax-lin-edit-2 width="14px" height="14px"/> Edit</button></form>
                            @endif
                            @if($isTeacher or $a->user_id == $me){!! $act('delete_answer', $a->id, 'Delete', 'lin-trash', true) !!}@endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($answers->hasPages())
                <div class="mt-24">{{ $answers->links('pagination::bootstrap-4') }}</div>
            @endif

            <form action="/course/{{ $course->slug }}/questions/{{ $question->id }}/answers" method="post" class="cvh-forum-box p-24 mt-24" id="answer-form">
                @csrf
                <h3 class="font-16 text-dark">Your answer</h3>
                <textarea name="body" rows="5" maxlength="10000" class="form-control mt-12 @error('body') is-invalid @enderror" placeholder="Share what you know..." required>{{ old('body') }}</textarea>
                @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-primary btn-lg mt-12">Post answer</button>
            </form>

            <div class="mt-24"><a href="/course/{{ $course->slug }}/questions" class="text-primary font-14">← All questions</a></div>
        </div>
    </main>
@endsection
