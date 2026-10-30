{{-- CVH custom section: partner organizations as cards, like the template's "Official Organizations". Content JSON: main_content{pre_title,title,description}, logos{key:{image,name}}, button{label,url} --}}
@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }
        $logos = array_values(array_filter($contents['logos'] ?? [], fn($l) => !empty($l['image'])));
        $visibleCount = 12; // the rest open with "Show all"
    @endphp

    @if(count($logos))
        @push('styles_top')
            <link rel="stylesheet" href="{{ getLandingComponentStylePath("organizations") }}">
            <link rel="stylesheet" href="{{ getDesign1StylePath("organizations_cards/grid_card_1") }}">
            <style>
                .cvh-organizations__logo { width: 72px; height: 72px; flex: 0 0 72px; padding: 8px; border-radius: 16px; background: #ffffff; border: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: center; }
                .cvh-organizations__logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
                .cvh-organizations__name { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
                .cvh-organizations:not(.is-expanded) .cvh-organizations__more { display: none; }
            </style>
        @endpush

        <div class="organizations-section cvh-organizations position-relative" id="cvhOrganizations">
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

                <div class="row">
                    @foreach($logos as $index => $logo)
                        <div class="col-12 col-md-6 col-lg-4 mt-24 {{ $index >= $visibleCount ? 'cvh-organizations__more' : '' }}">
                            <div class="organization-card position-relative h-100">
                                <div class="organization-card__mask"></div>

                                <div class="position-relative z-index-2 bg-white p-16 rounded-24 h-100">
                                    <div class="d-flex align-items-center p-12">
                                        <div class="cvh-organizations__logo">
                                            <img src="{{ $logo['image'] }}" alt="{{ $logo['name'] ?? '' }}" loading="lazy">
                                        </div>
                                        <div class="ml-16">
                                            <h6 class="cvh-organizations__name font-16 text-dark">{{ $logo['name'] ?? '' }}</h6>
                                            <span class="d-block font-14 text-gray-500 mt-4">Partner organization</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex-center flex-wrap gap-16 mt-40">
                    @if(count($logos) > $visibleCount)
                        <button type="button" class="btn btn-outline-primary btn-lg" aria-expanded="false" aria-controls="cvhOrganizations"
                                onclick="var s=document.getElementById('cvhOrganizations'),open=s.classList.toggle('is-expanded');this.setAttribute('aria-expanded',open);this.textContent=open?'Show fewer':'Show all {{ count($logos) }} organizations';">
                            Show all {{ count($logos) }} organizations
                        </button>
                    @endif

                    @if(!empty($contents['button']['label']) and !empty($contents['button']['url']))
                        <a href="{{ $contents['button']['url'] }}" class="btn btn-primary btn-lg">{{ $contents['button']['label'] }}</a>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endif
