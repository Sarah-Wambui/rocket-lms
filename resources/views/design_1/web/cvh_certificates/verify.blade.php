{{-- CVH Africa: public certificate verification. --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
@endpush

@section("content")
    <main class="pb-120">
        <section class="products-lists-hero position-relative">
            <div class="products-lists-hero__mask"></div>
            <img src="/store/cvh/courses/bundle-hd4a_cover.jpg" class="img-cover" alt="Verify a certificate"/>
        </section>

        <div class="container">
            <div class="products-lists-header position-relative z-index-2">
                <div class="products-lists-header__mask z-index-5"></div>
                <div class="position-relative bg-white rounded-32 z-index-8 p-24 p-lg-32">
                    <div class="d-flex-center size-64 rounded-12 bg-accent-30">
                        <x-iconsax-bul-shield-tick class="icons text-accent" width="32px" height="32px"/>
                    </div>
                    <h1 class="font-24 font-weight-bold mt-16">Verify a Certificate</h1>
                    <div class="font-14 text-gray-500 mt-8">Enter the certificate ID printed on a CVH Africa certificate of completion (e.g. CVH-2026-AB12CD34), or scan its QR code.</div>

                    <form action="/certificates/verify" method="get" class="d-flex flex-wrap gap-12 mt-24">
                        <input type="text" name="code" value="{{ $code }}" placeholder="Certificate ID" class="form-control" style="max-width: 360px;" required>
                        <button type="submit" class="btn btn-primary btn-lg">Verify</button>
                    </form>
                </div>
            </div>

            @if($code !== '')
                @if($certificate)
                    <div class="bg-white rounded-24 p-24 p-lg-32 mt-24" style="border: 2px solid var(--accent);">
                        <div class="d-flex align-items-center gap-8 font-16 font-weight-bold" style="color: var(--accent);">
                            <x-iconsax-bol-tick-circle width="24px" height="24px"/> Valid certificate
                        </div>
                        <div class="row mt-16 font-14">
                            <div class="col-12 col-md-6 mt-8"><span class="text-gray-500">Awarded to</span><div class="font-18 font-weight-bold text-dark">{{ $certificate->student_name }}</div></div>
                            <div class="col-12 col-md-6 mt-8"><span class="text-gray-500">Course</span><div class="font-16 font-weight-bold text-dark">{{ $certificate->course_title }}</div></div>
                            @if(!empty($certificate->quiz_id))
                                <div class="col-12 col-md-6 mt-16"><span class="text-gray-500">Assessment passed</span><div class="font-16 font-weight-bold text-dark">{{ $certificate->quiz_title }}</div></div>
                                <div class="col-12 col-md-6 mt-16"><span class="text-gray-500">Grade</span><div class="text-dark">{{ $certificate->grade }}</div></div>
                            @endif
                            <div class="col-12 col-md-6 mt-16"><span class="text-gray-500">Issued on</span><div class="text-dark">{{ dateTimeFormat($certificate->issued_at, 'j F Y', false) }}</div></div>
                            <div class="col-12 col-md-6 mt-16"><span class="text-gray-500">Certificate ID</span><div class="text-dark">{{ $certificate->code }}</div></div>
                        </div>
                        <div class="font-12 text-gray-500 mt-16">Issued by {{ config('cvh_certificate.issuer') }}.</div>
                    </div>
                @else
                    <div class="bg-white rounded-24 p-24 mt-24" style="border: 2px solid var(--danger);">
                        <div class="d-flex align-items-center gap-8 font-16 font-weight-bold" style="color: var(--danger);">
                            <x-iconsax-bol-close-circle width="24px" height="24px"/> No certificate found
                        </div>
                        <div class="font-14 text-gray-500 mt-8">We couldn't find a certificate with the ID "{{ $code }}". Check the ID and try again.</div>
                    </div>
                @endif
            @endif
        </div>
    </main>
@endsection
