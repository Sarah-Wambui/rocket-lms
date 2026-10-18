{{-- CVH custom section: partner logo strip. Content JSON: main_content{pre_title,title,description}, logos{key:{image,name}}, button{label,url} --}}
@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }
        $logos = array_values(array_filter($contents['logos'] ?? [], fn($l) => !empty($l['image'])));
    @endphp

    @if(count($logos))
        @push('styles_top')
            <style>
                .cvh-partners { padding: 24px 0; }
                .cvh-partners__viewport { overflow: hidden; -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
                .cvh-partners__track { display: flex; gap: 16px; width: max-content; animation: cvh-partners-scroll 90s linear infinite; }
                .cvh-partners__viewport:hover .cvh-partners__track { animation-play-state: paused; }
                .cvh-partners__tile { flex: 0 0 auto; width: 164px; height: 96px; display: flex; align-items: center; justify-content: center; padding: 12px; background: #ffffff; border: 1px solid var(--gray-300); border-radius: 16px; }
                .cvh-partners__tile img { max-width: 100%; max-height: 100%; object-fit: contain; }
                @keyframes cvh-partners-scroll { to { transform: translateX(-50%); } }
                @media (prefers-reduced-motion: reduce) {
                    .cvh-partners__track { animation: none; flex-wrap: wrap; justify-content: center; width: auto; }
                    .cvh-partners__track [aria-hidden="true"] { display: none; }
                }
            </style>
        @endpush

        <section class="cvh-partners position-relative">
            <div class="container">
                <div class="d-flex-center flex-column text-center">
                    @if(!empty($contents['main_content']['pre_title']))
                        <div class="d-inline-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                    @endif

                    @if(!empty($contents['main_content']['title']))
                        <h2 class="mt-12 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                    @endif

                    @if(!empty($contents['main_content']['description']))
                        <p class="mt-16 font-16 text-gray-500">{!! nl2br(e($contents['main_content']['description'])) !!}</p>
                    @endif
                </div>
            </div>

            <div class="cvh-partners__viewport mt-32">
                <div class="cvh-partners__track">
                    {{-- The list is rendered twice so the loop scrolls seamlessly; the copy is hidden from screen readers. --}}
                    @foreach([false, true] as $isCopy)
                        @foreach($logos as $logo)
                            <div class="cvh-partners__tile" @if($isCopy) aria-hidden="true" @endif title="{{ $logo['name'] ?? '' }}">
                                <img src="{{ $logo['image'] }}" alt="{{ $isCopy ? '' : ($logo['name'] ?? '') }}" loading="lazy">
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            @if(!empty($contents['button']['label']) and !empty($contents['button']['url']))
                <div class="d-flex-center mt-32">
                    <a href="{{ $contents['button']['url'] }}" class="btn btn-primary btn-lg">{{ $contents['button']['label'] }}</a>
                </div>
            @endif
        </section>
    @endif
@endif
