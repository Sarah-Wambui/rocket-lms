{{-- CVH Africa: a single discussion with its replies and the reply form. --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
@endpush

@php
    $moderate = function ($action, $id, $label, $icon, $danger = false) {
        $confirm = $danger ? ' onsubmit="return confirm(\'Delete this permanently from the forum?\')"' : '';
        return '<form action="/community/moderate" method="post" class="cvh-forum-mod"' . $confirm . '>'
            . csrf_field()
            . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . (int)$id . '">'
            . '<button type="submit"' . ($danger ? ' class="is-danger"' : '') . '>' . svg("iconsax-{$icon}", ['width' => '14px', 'height' => '14px'])->toHtml() . ' ' . e($label) . '</button></form>';
    };
    $authorLine = function ($user, $timestamp) {
        $badge = ($user and $user->role_name === 'admin') ? ' <span class="cvh-forum-tag cvh-forum-tag--admin">CVH Team</span>' : '';
        return '<div class="font-14 font-weight-bold text-dark">' . e($user->full_name ?? 'Former member') . $badge . '</div>'
            . '<div class="font-12 text-gray-500">' . e(dateTimeFormat($timestamp, 'j M Y, H:i', false)) . '</div>';
    };
    $author = $users[$topic->user_id] ?? null;
@endphp

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_community.header', [
            'title' => $topic->title,
            'subtitle' => null,
            'crumbs' => [['Community', '/community'], [$topic->category_title, '/community/' . $topic->category_slug], ['Discussion', null]],
        ])

        <div class="container">
            <div class="d-flex flex-wrap align-items-center gap-8 mt-24">
                <a href="/community/{{ $topic->category_slug }}" class="cvh-forum-tag cvh-forum-tag--cat">{{ $topic->category_title }}</a>
                @if($topic->is_pinned)<span class="cvh-forum-tag cvh-forum-tag--pinned"><x-iconsax-bol-star-1 width="12px" height="12px"/> Pinned</span>@endif
                @if($topic->is_locked)<span class="cvh-forum-tag cvh-forum-tag--locked"><x-iconsax-bol-lock width="12px" height="12px"/> Locked</span>@endif
                <span class="cvh-forum-meta ml-auto"><x-iconsax-lin-eye width="14px" height="14px"/> {{ $topic->views + 1 }} views · {{ $topic->replies_count }} {{ $topic->replies_count == 1 ? 'reply' : 'replies' }}</span>
            </div>

            <div class="cvh-forum-box mt-16">
                {{-- Original post (first page only) --}}
                @if($replies->currentPage() === 1)
                    <div class="cvh-forum-post">
                        <div class="d-flex align-items-center gap-12">
                            <img src="{{ $author ? $author->getAvatar(52) : getDefaultAvatarPath() }}" alt="" class="cvh-forum-avatar cvh-forum-avatar--lg">
                            <div>{!! $authorLine($author, $topic->created_at) !!}</div>
                        </div>
                        <div class="cvh-forum-post__body mt-16">{!! nl2br(e($topic->body)) !!}</div>

                        @if($isModerator)
                            <div class="d-flex flex-wrap gap-4 mt-16">
                                {!! $moderate($topic->is_pinned ? 'unpin' : 'pin', $topic->id, $topic->is_pinned ? 'Unpin' : 'Pin', 'bol-star-1') !!}
                                {!! $moderate($topic->is_locked ? 'unlock' : 'lock', $topic->id, $topic->is_locked ? 'Unlock' : 'Lock', $topic->is_locked ? 'lin-unlock' : 'lin-lock') !!}
                                {!! $moderate('delete_topic', $topic->id, 'Delete discussion', 'lin-trash', true) !!}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Replies --}}
                @foreach($replies as $reply)
                    @php $replyAuthor = $users[$reply->user_id] ?? null; @endphp
                    <div class="cvh-forum-post" id="reply-{{ $reply->id }}">
                        <div class="d-flex align-items-center gap-12">
                            <img src="{{ $replyAuthor ? $replyAuthor->getAvatar(40) : getDefaultAvatarPath() }}" alt="" class="cvh-forum-avatar">
                            <div>{!! $authorLine($replyAuthor, $reply->created_at) !!}</div>
                        </div>
                        <div class="cvh-forum-post__body mt-12">{!! nl2br(e($reply->body)) !!}</div>

                        @if($isModerator)
                            <div class="mt-8">{!! $moderate('delete_reply', $reply->id, 'Delete reply', 'lin-trash', true) !!}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($replies->hasPages())
                <div class="mt-24">{{ $replies->links('pagination::bootstrap-4') }}</div>
            @endif

            {{-- Reply form --}}
            <div class="cvh-forum-box p-24 mt-24" id="reply-form">
                @if($topic->is_locked and !$isModerator)
                    <div class="text-gray-500 font-14"><x-iconsax-bol-lock width="16px" height="16px"/> This discussion is locked; new replies are closed.</div>
                @elseif(auth()->check())
                    <h3 class="font-16 text-dark">Add a reply</h3>
                    <form action="/community/topic/{{ $topic->id }}/reply" method="post" class="mt-12">
                        @csrf
                        <textarea name="body" rows="5" maxlength="10000" class="form-control @error('body') is-invalid @enderror" placeholder="Share your answer or experience..." required>{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary btn-lg mt-12">Post reply</button>
                    </form>
                @else
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-12">
                        <span class="font-14 text-gray-500">Sign in to join the discussion.</span>
                        <div class="d-flex gap-8">
                            <a href="/login" class="btn btn-primary btn-lg">Sign in</a>
                            <a href="/register" class="btn btn-outline-primary btn-lg">Sign up</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection
