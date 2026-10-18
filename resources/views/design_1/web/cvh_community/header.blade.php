{{-- Shared hero + header card. Params: $title, $subtitle, $crumbs (array of [label, url|null]), optional $action (html) --}}
<section class="products-lists-hero position-relative">
    <div class="products-lists-hero__mask"></div>
    <img src="/store/cvh/courses/climate-agriculture_cover.jpg" class="img-cover" alt="Community Forum"/>
</section>

<div class="container">
    <div class="products-lists-header position-relative z-index-2">
        <div class="products-lists-header__mask z-index-5"></div>
        <div class="position-relative d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-16 bg-white rounded-32 z-index-8 p-24 p-lg-32">
            <div class="d-flex flex-column">
                <div class="d-flex-center size-64 rounded-12 bg-accent-30">
                    <x-iconsax-bul-messages-2 class="icons text-accent" width="32px" height="32px"/>
                </div>

                <div class="d-flex align-items-center flex-wrap mt-16 text-gray-500">
                    <a href="/" class="text-gray-500">{{ getPlatformName() }}</a>
                    @foreach($crumbs as [$label, $url])
                        <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                        @if($url)
                            <a href="{{ $url }}" class="text-gray-500">{{ $label }}</a>
                        @else
                            <span>{{ $label }}</span>
                        @endif
                    @endforeach
                </div>

                <h1 class="font-24 font-weight-bold mt-12">{{ $title }}</h1>
                @if(!empty($subtitle))
                    <div class="font-14 text-gray-500 mt-8">{{ $subtitle }}</div>
                @endif
            </div>

            @if(!empty($action))
                <div class="flex-shrink-0">{!! $action !!}</div>
            @endif
        </div>
    </div>

    @if(session('cvh_forum_status'))
        <div class="cvh-forum-alert mt-24">{{ session('cvh_forum_status') }}</div>
    @endif
</div>
