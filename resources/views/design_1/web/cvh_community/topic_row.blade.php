{{-- One discussion row. Params: $topic (with category_slug/category_title), $users (keyed by id), $showCategory --}}
@php
    $author = $users[$topic->user_id] ?? null;
    $lastActivity = max($topic->created_at, $topic->last_reply_at ?? 0);
@endphp
<div class="cvh-forum-row">
    <img src="{{ $author ? $author->getAvatar(48) : getDefaultAvatarPath() }}" alt="{{ $author->full_name ?? '' }}" class="cvh-forum-avatar">

    <div class="cvh-forum-row__main">
        <div class="d-flex align-items-center flex-wrap gap-6">
            @if($topic->is_pinned)
                <span class="cvh-forum-tag cvh-forum-tag--pinned"><x-iconsax-bol-star-1 width="12px" height="12px"/> Pinned</span>
            @endif
            @if($topic->is_locked)
                <span class="cvh-forum-tag cvh-forum-tag--locked"><x-iconsax-bol-lock width="12px" height="12px"/> Locked</span>
            @endif
            @if(!empty($showCategory))
                <a href="/community/{{ $topic->category_slug }}" class="cvh-forum-tag cvh-forum-tag--cat">{{ $topic->category_title }}</a>
            @endif
        </div>

        <a href="/community/topic/{{ $topic->id }}" class="cvh-forum-row__title mt-4">{{ $topic->title }}</a>

        <div class="font-12 text-gray-500 mt-4">
            {{ $author->full_name ?? 'Former member' }} · {{ dateTimeFormat($topic->created_at, 'j M Y', false) }}
            @if($topic->last_reply_at)
                · last reply {{ dateTimeFormatForHumans($lastActivity, true, null, 1) }}
            @endif
        </div>
    </div>

    <div class="cvh-forum-row__stats">
        <div class="cvh-forum-meta justify-content-end"><x-iconsax-lin-message width="14px" height="14px"/> {{ $topic->replies_count }} {{ $topic->replies_count == 1 ? 'reply' : 'replies' }}</div>
        <div class="cvh-forum-meta justify-content-end mt-4"><x-iconsax-lin-eye width="14px" height="14px"/> {{ $topic->views }} views</div>
    </div>
</div>
