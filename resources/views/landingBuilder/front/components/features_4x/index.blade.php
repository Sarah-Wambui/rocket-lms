{{-- CVH: front view for the template's "Features 4x" landing component (this template version shipped only its admin form and stylesheet). --}}
@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }

        $checkedItems = [];
        foreach (($contents['checked_items'] ?? []) as $key => $item) {
            $text = is_array($item) ? ($item['title'] ?? '') : $item;
            if ($key !== 'record' and !empty($text)) {
                $checkedItems[] = $text;
            }
        }

        $features = array_values(array_filter($contents['features_cards'] ?? [], fn($item, $key) => $key !== 'record' && !empty($item['title']), ARRAY_FILTER_USE_BOTH));
    @endphp

    @push('styles_top')
        <link rel="stylesheet" href="{{ getLandingComponentStylePath("features_4x") }}">
    @endpush

    <div class="features-4x-section position-relative">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-5">
                    @if(!empty($contents['main_content']['pre_title']))
                        <div class="d-inline-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                    @endif

                    @if(!empty($contents['main_content']['title']))
                        <h2 class="mt-12 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                    @endif

                    @if(!empty($contents['main_content']['description']))
                        <p class="mt-16 font-16 text-gray-500">{!! nl2br(e($contents['main_content']['description'])) !!}</p>
                    @endif

                    @if(count($checkedItems))
                        <div class="d-grid grid-columns-2 gap-16 mt-24">
                            @foreach($checkedItems as $checkedItem)
                                <div class="d-flex align-items-center gap-8 font-14 text-dark">
                                    <span class="d-flex-center size-24 rounded-circle bg-primary flex-shrink-0">
                                        <x-tick-icon class="icons text-white" width="14px" height="14px"/>
                                    </span>
                                    <span>{{ $checkedItem }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($contents['main_content']['button']['label']))
                        <a href="{{ $contents['main_content']['button']['url'] ?? '' }}" class="btn-flip-effect btn btn-primary btn-xlg gap-8 mt-32 text-white" data-text="{{ $contents['main_content']['button']['label'] }}">
                            @if(!empty($contents['main_content']['button']['icon']))
                                @svg("iconsax-{$contents['main_content']['button']['icon']}", ['width' => '24px', 'height' => '24px', 'class' => "icons"])
                            @endif
                            <span class="btn-flip-effect__text text-white">{{ $contents['main_content']['button']['label'] }}</span>
                        </a>
                    @endif
                </div>

                @if(count($features))
                    <div class="col-12 col-lg-7 mt-32 mt-lg-0">
                        <div class="row">
                            @foreach($features as $index => $feature)
                                <div class="col-12 col-md-6">
                                    @php $tag = !empty($feature['url']) ? 'a' : 'div'; @endphp
                                    <{{ $tag }} @if($tag === 'a') href="{{ $feature['url'] }}" @endif class="features-4x-section__card-item features-4x-section__card-item-{{ $index % 4 + 1 }} position-relative d-block text-decoration-none">
                                        <div class="features-4x-section__card-item-mask"></div>

                                        <div class="position-relative z-index-2 d-flex flex-column h-100 bg-white p-24 rounded-24">
                                            @if(!empty($feature['icon']))
                                                {{-- each card its own colour, like the trending category icons --}}
                                                @php $iconColor = ['#0170ff', '#5eac49', '#ffa200', '#f63c3c'][$index % 4]; @endphp
                                                <div class="d-flex-center size-56 rounded-16" style="background-color: {{ $iconColor }}1f; color: {{ $iconColor }}">
                                                    @svg("iconsax-{$feature['icon']}", ['width' => '28px', 'height' => '28px', 'class' => "icons"])
                                                </div>
                                            @endif

                                            <h3 class="mt-auto font-20 text-dark">{{ $feature['title'] }}</h3>

                                            @if(!empty($feature['description']))
                                                <p class="mt-8 font-14 text-gray-500">{{ $feature['description'] }}</p>
                                            @endif
                                        </div>
                                    </{{ $tag }}>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
