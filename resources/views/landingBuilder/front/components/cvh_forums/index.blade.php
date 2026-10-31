{{-- CVH custom section: the community forum (/community), laid out like the demo's Forums section: a white card over a dotted
     primary-colour band, with a row of forum members' avatars (CVH avatars fill the row when there are fewer than 7).
     Content JSON: main_content{pre_title,title,description}, primary_button{label,url}, secondary_button{label,url} --}}
@if(!empty($landingComponent) and $landingComponent->enable and \Illuminate\Support\Facades\Schema::hasTable('cvh_forum_topics'))
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }

        // the most recent people to start a forum discussion, with a profile photo
        $forumAvatars = \Illuminate\Support\Facades\DB::table('cvh_forum_topics as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->whereNull('t.deleted_at')->whereNotNull('u.avatar')->where('u.avatar', '!=', '')
            ->orderByDesc('t.created_at')
            ->limit(30)
            ->pluck('u.avatar')->unique()->take(7)->values()->all();

        foreach (range(1, 5) as $n) {
            if (count($forumAvatars) >= 7) break;
            $forumAvatars[] = "/store/cvh/landing/avatar_{$n}.png";
        }
        foreach (['/store/landing_builder/landing_12/370/avatar1_GX4.png', '/store/landing_builder/landing_6/170/avatar2_ILx.png'] as $extra) {
            if (count($forumAvatars) >= 7) break;
            $forumAvatars[] = $extra;
        }
        $middleAvatar = intdiv(count($forumAvatars), 2);
    @endphp

    @push('styles_top')
        <style>
            .cvh-forums-section { padding-top: 60px; }
            .cvh-forums-section__band { position: absolute; left: 0; right: 0; top: 0; height: 75%; background-color: var(--primary); background-image: url(/store/landing_builder/landing_13/401/forums_section_CyQ.png); background-size: cover; background-position: center; }
            .cvh-forums-section__card { position: relative; max-width: 1120px; margin: 0 auto; padding: 60px 64px; border-radius: 40px; background: #ffffff; box-shadow: 0 24px 48px rgba(0, 0, 0, 0.04); }
            .cvh-forums-section__description { max-width: 1000px; margin-left: auto; margin-right: auto; }
            .cvh-forums-section__avatars { display: flex; align-items: center; justify-content: center; gap: 32px; }
            .cvh-forums-section__line { flex: 1; max-width: 160px; height: 1px; }
            .cvh-forums-section__line--start { background: linear-gradient(90deg, transparent, var(--gray-200)); }
            .cvh-forums-section__line--end { background: linear-gradient(90deg, var(--gray-200), transparent); }
            .cvh-forums-section__avatar { position: relative; flex-shrink: 0; border-radius: 50%; padding: 4px; }
            .cvh-forums-section__avatar img { display: block; width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
            .cvh-forums-section__avatar--1, .cvh-forums-section__avatar--7 { width: 40px; height: 40px; background: #f6efe9; }
            .cvh-forums-section__avatar--2, .cvh-forums-section__avatar--6 { width: 48px; height: 48px; background: #e8f4d9; }
            .cvh-forums-section__avatar--3, .cvh-forums-section__avatar--5 { width: 56px; height: 56px; background: #e6f6f1; }
            .cvh-forums-section__avatar--main { width: 72px; height: 72px; background: #eee9fb; }
            .cvh-forums-section__hi { position: absolute; right: -6px; bottom: -4px; padding: 2px 8px; border-radius: 12px; border: 2px solid #ffffff; background: #f2564f; color: #ffffff; font-size: 12px; font-weight: 700; }
            .cvh-forums-section__link { color: var(--dark); }
            .cvh-forums-section__link:hover { color: var(--primary); text-decoration: none; }
            .cvh-forums-section__plus { width: 24px; height: 24px; border-radius: 50%; background: var(--gray-500); color: #ffffff; }
            @media (max-width: 991.98px) {
                .cvh-forums-section__card { padding: 40px 24px; border-radius: 32px; margin: 0 16px; }
                .cvh-forums-section__avatars { gap: 12px; }
                .cvh-forums-section__line { display: none; }
            }
        </style>
    @endpush

    <div class="cvh-forums-section position-relative">
        <div class="cvh-forums-section__band"></div>

        <div class="container position-relative">
            <div class="cvh-forums-section__card text-center">
                @if(!empty($contents['main_content']['pre_title']))
                    <div class="d-inline-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                @endif

                @if(!empty($contents['main_content']['title']))
                    <h2 class="mt-12 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                @endif

                @if(!empty($contents['main_content']['description']))
                    <p class="cvh-forums-section__description mt-16 font-16 text-gray-500">{!! nl2br(e($contents['main_content']['description'])) !!}</p>
                @endif

                <div class="cvh-forums-section__avatars mt-48">
                    <span class="cvh-forums-section__line cvh-forums-section__line--start"></span>

                    @foreach($forumAvatars as $index => $avatar)
                        @php
                            $sizeClass = $index === $middleAvatar ? 'main' : (abs($index - $middleAvatar) >= 3 ? '1' : (abs($index - $middleAvatar) == 2 ? '2' : '3'));
                        @endphp
                        <div class="cvh-forums-section__avatar cvh-forums-section__avatar--{{ $sizeClass }} {{ abs($index - $middleAvatar) >= 2 ? 'd-none d-md-block' : '' }}">
                            <img src="{{ $avatar }}" alt="" loading="lazy">
                            @if($index === $middleAvatar)
                                <span class="cvh-forums-section__hi">Hi!</span>
                            @endif
                        </div>
                    @endforeach

                    <span class="cvh-forums-section__line cvh-forums-section__line--end"></span>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-center gap-48 mt-56">
                    @if(!empty($contents['primary_button']['label']))
                        <a href="{{ $contents['primary_button']['url'] ?? '/community' }}" class="btn-flip-effect btn btn-primary btn-xlg gap-8 text-white" data-text="{{ $contents['primary_button']['label'] }}">
                            <x-iconsax-bul-messages-2 class="icons" width="24px" height="24px"/>
                            <span class="btn-flip-effect__text text-white">{{ $contents['primary_button']['label'] }}</span>
                        </a>
                    @endif

                    @if(!empty($contents['secondary_button']['label']))
                        <a href="{{ $contents['secondary_button']['url'] ?? '/community/new' }}" class="cvh-forums-section__link d-flex align-items-center gap-12 font-16">
                            <span class="cvh-forums-section__plus d-flex-center">
                                <x-iconsax-lin-add class="icons" width="16px" height="16px"/>
                            </span>
                            <span>{{ $contents['secondary_button']['label'] }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
