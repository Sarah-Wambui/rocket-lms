{{-- CVH: front view for the template's "Links and titles slider (1 row)" landing component: one band of links scrolling
     across the page. With no items set in the admin it lists every enabled course category.
     (This template version shipped only its admin form and stylesheet; the scrolling is CSS, no slider script.) --}}
@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }

        $items = [];
        foreach (($contents['title_items'] ?? []) as $key => $item) {
            if ($key !== 'record' and !empty($item['title'])) {
                $items[] = ['title' => $item['title'], 'url' => $item['url'] ?? ''];
            }
        }
        if (!count($items)) {
            foreach (\App\Models\Category::query()->where('enable', true)->orderBy('order')->get() as $category) {
                $items[] = ['title' => $category->title, 'url' => $category->getUrl()];
            }
        }

        // repeat short lists so the band is wider than the screen, then render it twice for a seamless loop
        $band = $items ? array_merge(...array_fill(0, max(1, (int)ceil(12 / count($items))), $items)) : [];
        $separatorIcon = !empty($contents['separator_icon']) ? $contents['separator_icon'] : 'bul-diamonds';
        $tape = ($contents['card_color'] ?? 'secondary') === 'primary' ? 'primary-tape' : 'secondary-tape';
        $rotate = ($contents['card_style'] ?? 'normal') === 'rotate';
    @endphp

    @if(count($band))
        @push('styles_top')
            <link rel="stylesheet" href="{{ getLandingComponentStylePath("links_and_titles_slider_1_row") }}">
            <style>
                .cvh-tape { overflow: hidden; }
                .cvh-tape.taps-with-rotate { padding: 48px 0; }
                .cvh-tape .marquee-swiper { display: flex; overflow: hidden; }
                .cvh-tape__track { display: flex; flex-shrink: 0; width: max-content; animation: cvh-tape-scroll 80s linear infinite; }
                .cvh-tape .swiper-slide { padding-right: 32px; }
                .cvh-tape:hover .cvh-tape__track { animation-play-state: paused; }
                @keyframes cvh-tape-scroll { to { transform: translateX(-50%); } }
                @media (prefers-reduced-motion: reduce) { .cvh-tape__track { animation: none; } }
            </style>
        @endpush

        <div class="links-and-titles-slider-1-row-section cvh-tape {{ $rotate ? 'taps-with-rotate' : '' }}">
            <div class="marquee-swiper {{ $tape }}">
                <div class="cvh-tape__track">
                    @foreach([false, true] as $isCopy)
                        <div class="swiper-slide" @if($isCopy) aria-hidden="true" @endif>
                            @foreach($band as $item)
                                @if(!empty($item['url']))
                                    <a href="{{ $item['url'] }}" class="marquee-item" @if($isCopy) tabindex="-1" @endif>{{ $item['title'] }}</a>
                                @else
                                    <span class="marquee-item">{{ $item['title'] }}</span>
                                @endif
                                @svg("iconsax-{$separatorIcon}", ['class' => 'tape-separator-icon text-white'])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endif
