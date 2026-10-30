{{-- CVH custom section: "Official Organizations", every partner logo together in one panel. Content JSON: main_content{pre_title,title,description}, logos{key:{image,name}}, button{label,url} --}}
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
            <link rel="stylesheet" href="{{ getLandingComponentStylePath("organizations") }}">
            <style>
                .cvh-organizations__panel { padding: 40px; border-radius: 32px; background: #ffffff; border: 1px solid var(--gray-200); }
                .cvh-organizations__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: 32px 40px; align-items: center; }
                .cvh-organizations__logo { height: 64px; display: flex; align-items: center; justify-content: center; }
                .cvh-organizations__logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
                @media (max-width: 575.98px) {
                    .cvh-organizations__panel { padding: 24px 16px; border-radius: 24px; }
                    .cvh-organizations__grid { grid-template-columns: repeat(3, 1fr); gap: 24px 16px; }
                    .cvh-organizations__logo { height: 48px; }
                }
            </style>
        @endpush

        <div class="organizations-section position-relative">
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

                <div class="cvh-organizations__panel mt-40">
                    <ul class="cvh-organizations__grid list-unstyled m-0 p-0">
                        @foreach($logos as $logo)
                            <li class="cvh-organizations__logo" title="{{ $logo['name'] ?? '' }}">
                                <img src="{{ $logo['image'] }}" alt="{{ $logo['name'] ?? '' }}" loading="lazy">
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if(!empty($contents['button']['label']) and !empty($contents['button']['url']))
                    <div class="d-flex-center mt-32">
                        <a href="{{ $contents['button']['url'] }}" class="btn btn-primary btn-lg">{{ $contents['button']['label'] }}</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif
