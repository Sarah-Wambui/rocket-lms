{{-- Course Q&A header. Params: $course, $title, optional $crumbs ([label, url|null]), optional $action (html) --}}
<section class="products-lists-hero position-relative">
    <div class="products-lists-hero__mask"></div>
    <img src="{{ $course->getImageCover() ?: $course->getImage() }}" class="img-cover" alt="{{ $course->title }}"/>
</section>

<div class="container">
    <div class="products-lists-header position-relative z-index-2">
        <div class="products-lists-header__mask z-index-5"></div>
        <div class="position-relative d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-16 bg-white rounded-32 z-index-8 p-24 p-lg-32">
            <div class="d-flex flex-column" style="min-width: 0;">
                <div class="d-flex-center size-64 rounded-12 bg-accent-30">
                    <x-iconsax-bul-message-question class="icons text-accent" width="32px" height="32px"/>
                </div>
                <div class="d-flex align-items-center flex-wrap mt-16 text-gray-500 font-14">
                    <a href="{{ $course->getUrl() }}" class="text-gray-500">{{ \Illuminate\Support\Str::limit($course->title, 60) }}</a>
                    <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                    <a href="/course/{{ $course->slug }}/questions" class="text-gray-500">Q&A</a>
                    @foreach($crumbs ?? [] as [$label, $url])
                        <x-iconsax-lin-arrow-right-1 class="mx-4" width="16px" height="16px"/>
                        @if($url)<a href="{{ $url }}" class="text-gray-500">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
                    @endforeach
                </div>
                <h1 class="font-24 font-weight-bold mt-12">{{ $title }}</h1>
            </div>
            <div class="d-flex flex-wrap gap-8 flex-shrink-0">
                <a href="/course/learning/{{ $course->slug }}" class="btn btn-outline-primary btn-lg">Back to course</a>
                {!! $action ?? '' !!}
            </div>
        </div>
    </div>

    @if(session('cvh_qa_status'))
        <div class="cvh-forum-alert mt-24">{{ session('cvh_qa_status') }}</div>
    @endif
</div>
