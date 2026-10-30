{{-- CVH: front view for the template's "Links and titles slider (2 rows)" landing component: two coloured bands of links
     scrolling in opposite directions. With no items set in the admin it lists every enabled course category.
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

        // repeat short lists so each band is wider than the screen, then render it twice for a seamless loop
        $band = $items ? array_merge(...array_fill(0, max(1, (int)ceil(12 / count($items))), $items)) : [];
        $separatorIcon = !empty($contents['separator_icon']) ? $contents['separator_icon'] : 'bol-star-1';
        $rotate = ($contents['card_style'] ?? 'rotate') !== 'normal';
    @endphp

    @if(count($band))
        @push('styles_top')
            <link rel="stylesheet" href="{{ getLandingComponentStylePath("links_and_titles_slider_2_rows") }}">
            <style>
                .cvh-tapes { overflow: hidden; padding: 48px 0; }
                .cvh-tapes .marquee-swiper { display: flex; overflow: hidden; }
                .cvh-tapes .marquee-swiper + .marquee-swiper { margin-top: 12px; }
                /* both bands at the same angle so they run side by side instead of crossing over each other's text */
                .cvh-tapes.taps-with-rotate .marquee-swiper.primary-tape,
                .cvh-tapes.taps-with-rotate .marquee-swiper.secondary-tape { transform: rotate(-2deg); }
                .cvh-tapes__track { display: flex; flex-shrink: 0; width: max-content; animation: cvh-tapes-scroll 80s linear infinite; }
                .cvh-tapes .secondary-tape .cvh-tapes__track { animation-direction: reverse; }
                .cvh-tapes .swiper-slide { padding-right: 32px; }
                .cvh-tapes:hover .cvh-tapes__track { animation-play-state: paused; }
                @keyframes cvh-tapes-scroll { to { transform: translateX(-50%); } }
                @media (prefers-reduced-motion: reduce) { .cvh-tapes__track { animation: none; } }
            </style>
        @endpush

        <div class="links-and-titles-slider-2-rows-section cvh-tapes {{ $rotate ? 'taps-with-rotate' : '' }}">
            @foreach(['primary-tape', 'secondary-tape'] as $tape)
                <div class="marquee-swiper {{ $tape }}">
                    <div class="cvh-tapes__track">
                        @foreach([false, true] as $isCopy)
                            <div class="swiper-slide" @if($isCopy or $tape === 'secondary-tape') aria-hidden="true" @endif>
                                @foreach($band as $item)
                                    @if(!empty($item['url']))
                                        <a href="{{ $item['url'] }}" class="marquee-item" @if($isCopy or $tape === 'secondary-tape') tabindex="-1" @endif>{{ $item['title'] }}</a>
                                    @else
                                        <span class="marquee-item">{{ $item['title'] }}</span>
                                    @endif
                                    @svg("iconsax-{$separatorIcon}", ['class' => 'tape-separator-icon text-white'])
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif
