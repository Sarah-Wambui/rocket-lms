{{-- CVH Africa: innovations showcase, laid out like the store listing (hero + header card + filters + card grid). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_innovations.styles')
@endpush

@section("content")
    <main class="pb-120">
        <section class="products-lists-hero position-relative">
            <div class="products-lists-hero__mask"></div>
            <img src="/store/cvh/courses/bundle-hd4a_cover.jpg" class="img-cover" alt="Innovations"/>
        </section>

        <div class="container">
            {{-- Header --}}
            <div class="products-lists-header position-relative z-index-2">
                <div class="products-lists-header__mask z-index-5"></div>
                <div class="position-relative d-flex align-items-start bg-white rounded-32 z-index-8">
                    <div class="d-flex flex-column p-32">
                        <div class="d-flex-center size-64 rounded-12 bg-accent-30">
                            <x-iconsax-bul-lamp-on class="icons text-accent" width="32px" height="32px"/>
                        </div>

                        <div class="d-flex align-items-center mt-16 text-gray-500">
                            <a href="/" class="text-gray-500">{{ getPlatformName() }}</a>
                            <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                            <span>Innovations</span>
                        </div>

                        <h1 class="font-24 font-weight-bold mt-12">HD4A Innovations</h1>
                        <div class="font-14 text-gray-500 mt-8">
                            The innovators recognized in the <strong>HealthyDiets4Africa Agribusiness Accelerator Program</strong> don't just react to change; they designed for it.
                            Their portfolios shape agro-processing and service sectors, influence markets and set new standards for Africa's food systems.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <form action="/innovations" method="get" class="cvh-innov-filters bg-white rounded-24 p-16 mt-24">
                <div class="d-flex flex-wrap align-items-center gap-8">
                    @foreach($tabs as $key => $tab)
                        <a href="/innovations?{{ http_build_query(array_filter(['cohort' => $key, 'country' => $selectedCountry, 'search' => $search])) }}"
                           class="cvh-innov-tab {{ $selectedTab === (string)$key ? 'is-active' : '' }}">
                            {{ $tab['title'] }} <span class="cvh-innov-tab__count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap align-items-center gap-12 mt-16">
                    @if($selectedTab !== '')
                        <input type="hidden" name="cohort" value="{{ $selectedTab }}">
                    @endif

                    <div class="cvh-innov-search flex-grow-1">
                        <x-iconsax-lin-search-normal class="icons text-gray-500" width="20px" height="20px"/>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, business or location" class="form-control">
                    </div>

                    <select name="country" class="form-control cvh-innov-select" onchange="this.form.submit()">
                        <option value="">All countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ $selectedCountry === $country ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary btn-lg">Search</button>
                </div>
            </form>

            {{-- Results --}}
            <div class="d-flex align-items-center justify-content-between mt-32">
                <h2 class="font-20 text-dark">{{ $selectedTab === '' ? 'All Innovators' : $tabs[$selectedTab]['title'] }}</h2>
                <span class="font-14 text-gray-500">{{ count($innovators) }} {{ count($innovators) === 1 ? 'innovator' : 'innovators' }}</span>
            </div>

            @if(count($innovators))
                <div class="row">
                    @foreach($innovators as $innovator)
                        <div class="col-6 col-lg-4 col-xl-3 mt-24">
                            @include('design_1.web.cvh_innovations.card', ['innovator' => $innovator])
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-24 p-32 mt-24 text-center text-gray-500">
                    No innovators match your search. <a href="/innovations" class="text-primary">Show all innovators</a>
                </div>
            @endif
        </div>
    </main>
@endsection
