{{-- CVH custom section: the community forum (/community), like the demo's Forums section, with the latest discussions.
     Content JSON: main_content{pre_title,title,description}, primary_button{label,url}, secondary_button{label,url} --}}
@if(!empty($landingComponent) and $landingComponent->enable and \Illuminate\Support\Facades\Schema::hasTable('cvh_forum_topics'))
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }

        $forumTopics = \Illuminate\Support\Facades\DB::table('cvh_forum_topics as t')
            ->join('cvh_forum_categories as c', 'c.id', '=', 't.category_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->whereNull('t.deleted_at')->where('c.enable', 1)
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('greatest(t.created_at, coalesce(t.last_reply_at, 0))'))
            ->limit(4)
            ->get(['t.id', 't.title', 't.replies_count', 't.created_at', 't.last_reply_at', 'c.title as category', 'u.full_name as author']);

        $forumStats = [
            'topics' => \Illuminate\Support\Facades\DB::table('cvh_forum_topics')->whereNull('deleted_at')->count(),
            'replies' => \Illuminate\Support\Facades\DB::table('cvh_forum_replies')->whereNull('deleted_at')->count(),
        ];
    @endphp

    <div class="cvh-forums-section position-relative">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    @if(!empty($contents['main_content']['pre_title']))
                        <div class="d-inline-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                    @endif

                    @if(!empty($contents['main_content']['title']))
                        <h2 class="mt-12 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                    @endif

                    @if(!empty($contents['main_content']['description']))
                        <p class="mt-16 font-16 text-gray-500">{!! nl2br(e($contents['main_content']['description'])) !!}</p>
                    @endif

                    <div class="d-flex gap-32 mt-24">
                        <div>
                            <div class="font-24 font-weight-bold text-dark">{{ $forumStats['topics'] }}</div>
                            <div class="font-14 text-gray-500">Discussions</div>
                        </div>
                        <div>
                            <div class="font-24 font-weight-bold text-dark">{{ $forumStats['replies'] }}</div>
                            <div class="font-14 text-gray-500">Replies</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-16 mt-32">
                        @if(!empty($contents['primary_button']['label']))
                            <a href="{{ $contents['primary_button']['url'] ?? '/community' }}" class="btn-flip-effect btn btn-primary btn-xlg gap-8 text-white" data-text="{{ $contents['primary_button']['label'] }}">
                                <x-iconsax-bul-messages-2 class="icons" width="24px" height="24px"/>
                                <span class="btn-flip-effect__text text-white">{{ $contents['primary_button']['label'] }}</span>
                            </a>
                        @endif

                        @if(!empty($contents['secondary_button']['label']))
                            <a href="{{ $contents['secondary_button']['url'] ?? '/community/new' }}" class="btn btn-outline-primary btn-xlg gap-8">
                                <x-iconsax-lin-add class="icons" width="24px" height="24px"/>
                                <span>{{ $contents['secondary_button']['label'] }}</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-6 mt-32 mt-lg-0">
                    <div class="bg-white p-24 rounded-24 border-gray-200">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="font-16 text-dark">Latest discussions</h3>
                            <a href="/community" class="font-14 text-primary">View all</a>
                        </div>

                        @forelse($forumTopics as $forumTopic)
                            <a href="/community/topic/{{ $forumTopic->id }}" class="d-flex align-items-center gap-12 mt-16 p-16 rounded-16 bg-gray-100 text-decoration-none">
                                <div class="d-flex-center size-48 rounded-circle bg-primary-20 flex-shrink-0">
                                    <x-iconsax-bul-message-text class="icons text-primary" width="24px" height="24px"/>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="font-14 font-weight-bold text-dark text-ellipsis">{{ $forumTopic->title }}</div>
                                    <div class="font-12 text-gray-500 mt-4">
                                        {{ $forumTopic->category }}
                                        @if($forumTopic->author) · {{ $forumTopic->author }}@endif
                                        · {{ dateTimeFormat(max((int)$forumTopic->created_at, (int)$forumTopic->last_reply_at), 'j M Y', false) }}
                                    </div>
                                </div>
                                <div class="text-center flex-shrink-0">
                                    <div class="font-16 font-weight-bold text-dark">{{ $forumTopic->replies_count }}</div>
                                    <div class="font-12 text-gray-500">replies</div>
                                </div>
                            </a>
                        @empty
                            <div class="mt-16 p-24 rounded-16 bg-gray-100 text-center font-14 text-gray-500">
                                No discussions yet. <a href="/community/new" class="text-primary">Start the first one</a>.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
