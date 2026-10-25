{{-- CVH Africa: learner's courses, progress and certificate downloads. --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    <style>
        .cvh-cert-row { display: flex; gap: 16px; align-items: center; padding: 16px 20px; border-top: 1px solid var(--gray-200); }
        .cvh-cert-row:first-child { border-top: 0; }
        .cvh-cert-thumb { width: 96px; height: 66px; border-radius: 12px; object-fit: cover; flex: 0 0 auto; background: var(--gray-100); }
        .cvh-cert-bar { height: 8px; border-radius: 8px; background: var(--gray-200); overflow: hidden; }
        .cvh-cert-bar > span { display: block; height: 100%; background: var(--primary); }
        .cvh-cert-bar.is-done > span { background: var(--accent); }
        @media (max-width: 575.98px) { .cvh-cert-row { flex-wrap: wrap; } .cvh-cert-thumb { width: 72px; height: 50px; } }
    </style>
@endpush

@section("content")
    <main class="pb-120">
        <section class="products-lists-hero position-relative">
            <div class="products-lists-hero__mask"></div>
            <img src="/store/cvh/courses/bundle-hd4a_cover.jpg" class="img-cover" alt="My Certificates"/>
        </section>

        <div class="container">
            <div class="products-lists-header position-relative z-index-2">
                <div class="products-lists-header__mask z-index-5"></div>
                <div class="position-relative bg-white rounded-32 z-index-8 p-24 p-lg-32">
                    <div class="d-flex-center size-64 rounded-12 bg-accent-30">
                        <x-iconsax-bul-medal-star class="icons text-accent" width="32px" height="32px"/>
                    </div>
                    <h1 class="font-24 font-weight-bold mt-16">My Certificates</h1>
                    <div class="font-14 text-gray-500 mt-8">Complete every lesson in a course to unlock your free CVH Africa certificate of completion.</div>
                </div>
            </div>

            @if(session('cvh_cert_status'))
                <div class="mt-24 p-16 rounded-12 font-14" style="background: rgba(1,112,255,.08); color: var(--primary);">{{ session('cvh_cert_status') }}</div>
            @endif

            <div class="bg-white rounded-24 mt-24" style="border: 1px solid var(--gray-200);">
                @forelse($rows as $row)
                    @php $course = $row['course']; $done = $row['progress'] >= 100; @endphp
                    <div class="cvh-cert-row">
                        <img src="{{ $course->getImage() }}" alt="" class="cvh-cert-thumb">
                        <div class="flex-grow-1" style="min-width: 0;">
                            <a href="{{ $course->getUrl() }}" class="font-15 font-weight-bold text-dark">{{ $course->title }}</a>
                            <div class="d-flex align-items-center gap-8 mt-8">
                                <div class="cvh-cert-bar flex-grow-1 {{ $done ? 'is-done' : '' }}"><span style="width: {{ min(100, $row['progress']) }}%"></span></div>
                                <span class="font-12 text-gray-500">{{ round($row['progress']) }}%</span>
                            </div>
                            @if($row['certificate'])
                                <div class="font-12 text-gray-500 mt-4">Certificate ID: {{ $row['certificate']->code }}</div>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @if($done or $row['certificate'] or $row['earned'])
                                <a href="/my-certificates/{{ $course->id }}/download" class="btn btn-primary btn-lg">Download certificate</a>
                            @else
                                <a href="/course/learning/{{ $course->slug }}" class="btn btn-outline-primary btn-lg">Continue learning</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-32 text-center text-gray-500">
                        You are not enrolled in any courses yet. <a href="/classes" class="text-primary">Explore free courses</a>
                    </div>
                @endforelse
            </div>

            <p class="font-12 text-gray-500 mt-16">Anyone can confirm a certificate is genuine at <a href="/certificates/verify" class="text-primary">{{ rtrim(config('app.url'), '/') }}/certificates/verify</a>.</p>
        </div>
    </main>
@endsection
