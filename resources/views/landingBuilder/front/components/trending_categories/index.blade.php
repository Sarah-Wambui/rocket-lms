@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }
    @endphp

    @push('styles_top')
        <link rel="stylesheet" href="{{ getLandingComponentStylePath("trending_categories") }}">
    @endpush

    <div class="trending-categories-section position-relative" @if(!empty($contents['background'])) style="background-image: url({{ $contents['background'] }})" @endif>

        <div class="container position-relative">

            @if(!empty($contents['floating_background']))
                <div class="trending-categories-section__floating-background d-flex-center">
                    <img src="{{ $contents['floating_background'] }}" alt="floating_background" class="">
                </div>
            @endif


            <div class="d-flex-center flex-column text-center">
                @if(!empty($contents['main_content']) and !empty($contents['main_content']['pre_title']))
                    <div class="d-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                @endif

                @if(!empty($contents['main_content']) and !empty($contents['main_content']['title']))
                    <h2 class="mt-8 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                @endif
            </div>

        </div>

        {{-- CVH: categories flow across the page in a loop (was a static grid). The list is rendered twice so the loop is seamless; the copy is hidden from screen readers. --}}
        @php
            $trendingCategories = [];
            foreach (($contents['trending_categories'] ?? []) as $trendingCategoryData) {
                if (!empty($trendingCategoryData['category'])) {
                    $trendingCategory = \App\Models\TrendCategory::query()->where('id', $trendingCategoryData['category'])->first();
                    if (!empty($trendingCategory) and !empty($trendingCategory->category)) {
                        $trendingCategories[] = $trendingCategory;
                    }
                }
            }
        @endphp

        @if(count($trendingCategories))
            @push('styles_top')
                <style>
                    .cvh-trending-flow { overflow: hidden; padding: 16px 0 24px; -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
                    .cvh-trending-flow__track { display: flex; gap: 24px; width: max-content; animation: cvh-trending-flow 60s linear infinite; }
                    .cvh-trending-flow:hover .cvh-trending-flow__track, .cvh-trending-flow:focus-within .cvh-trending-flow__track { animation-play-state: paused; }
                    .cvh-trending-flow__item { flex: 0 0 auto; width: 240px; }
                    @keyframes cvh-trending-flow { to { transform: translateX(calc(-50% - 12px)); } }
                    .rtl .cvh-trending-flow__track { animation-direction: reverse; }
                    @media (prefers-reduced-motion: reduce) {
                        .cvh-trending-flow__track { animation: none; flex-wrap: wrap; justify-content: center; width: auto; }
                        .cvh-trending-flow__track [aria-hidden="true"] { display: none; }
                    }
                </style>
            @endpush

            <div class="cvh-trending-flow mt-24">
                <div class="cvh-trending-flow__track">
                    @foreach([false, true] as $isCopy)
                        @foreach($trendingCategories as $trendingCategory)
                            <a href="{{ $trendingCategory->category->getUrl() }}" class="cvh-trending-flow__item" @if($isCopy) aria-hidden="true" tabindex="-1" @endif>
                                <div class="trending-categories-section__item-card position-relative">
                                    <div class="trending-categories-section__item-card-mask"></div>

                                    <div class="position-relative bg-white p-16 rounded-24 z-index-2">
                                        <div class="d-inline-flex-center size-48 rounded-circle " style="background-color: {{ $trendingCategory->color }}">
                                            <img src="{{ $trendingCategory->icon }}" alt="{{ $isCopy ? '' : $trendingCategory->category->title }}" class="img-fluid" width="24px" height="24px">
                                        </div>

                                        <h4 class="mt-16 font-20 text-dark">{{ $trendingCategory->category->title }}</h4>
                                        <div class="mt-8 font-16 text-gray-500">{{ trans('update.count_courses', ['count' => $trendingCategory->category->getCoursesCount()]) }}</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
