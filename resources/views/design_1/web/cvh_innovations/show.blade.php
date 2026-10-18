{{-- CVH Africa: single innovator profile (custom page). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_innovations.styles')
@endpush

@section("content")
    <main class="pb-120">
        <section class="products-lists-hero position-relative">
            <div class="products-lists-hero__mask"></div>
            <img src="/store/cvh/courses/bundle-hd4a_cover.jpg" class="img-cover" alt="{{ $innovator->business }}"/>
        </section>

        <div class="container">
            <div class="products-lists-header position-relative z-index-2">
                <div class="products-lists-header__mask z-index-5"></div>
                <div class="position-relative bg-white rounded-32 z-index-8 p-24 p-lg-32">
                    <div class="d-flex align-items-center flex-wrap text-gray-500">
                        <a href="/" class="text-gray-500">{{ getPlatformName() }}</a>
                        <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                        <a href="/innovations" class="text-gray-500">Innovations</a>
                        <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                        <span>{{ $innovator->business }}</span>
                    </div>

                    <div class="row mt-16">
                        <div class="col-12 col-md-4 col-lg-3">
                            <img src="{{ $innovator->photo }}" alt="{{ $innovator->name }}" class="cvh-innov-profile__photo">
                        </div>

                        <div class="col-12 col-md-8 col-lg-9 mt-16 mt-md-0">
                            <div class="d-flex flex-wrap gap-8">
                                <span class="cvh-innov-tab is-active">Cohort {{ $innovator->cohort }}</span>
                                @if($innovator->is_winner)
                                    <span class="cvh-innov-tab" style="background: var(--accent); border-color: var(--accent); color: #fff;">
                                        <x-iconsax-bol-medal-star width="14px" height="14px"/> Winner
                                    </span>
                                @endif
                            </div>

                            <h1 class="font-32 text-dark mt-16">{{ $innovator->business }}</h1>
                            <div class="font-16 text-gray-500 mt-4">{{ $innovator->name }}</div>

                            <div class="d-flex align-items-center gap-4 font-14 text-gray-500 mt-8">
                                <x-iconsax-lin-location width="16px" height="16px"/>
                                <span>{{ $innovator->location }}</span>
                            </div>

                            @if(!empty($innovator->summary))
                                <p class="font-16 text-gray-500 mt-16">{{ $innovator->summary }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($innovator->profile_html))
                <div class="bg-white rounded-32 p-24 p-lg-32 mt-24 cvh-innov-profile__content">
                    {!! $innovator->profile_html !!}
                </div>
            @endif

            @if(count($related))
                <div class="d-flex align-items-center justify-content-between mt-40">
                    <h2 class="font-20 text-dark">More from Cohort {{ $innovator->cohort }}</h2>
                    <a href="/innovations?cohort={{ $innovator->cohort }}" class="font-14 text-primary">View all</a>
                </div>

                <div class="row">
                    @foreach($related as $item)
                        <div class="col-6 col-lg-3 mt-24">
                            @include('design_1.web.cvh_innovations.card', ['innovator' => $item])
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-40">
                <a href="/innovations" class="btn btn-primary btn-lg">
                    <x-iconsax-lin-arrow-left width="18px" height="18px"/> <span class="ml-4">All innovators</span>
                </a>
            </div>
        </div>
    </main>
@endsection
