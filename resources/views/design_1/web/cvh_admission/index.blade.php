{{-- CVH Africa: learner's admission steps (application form -> admission fee -> review). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    <style>
        .cvh-adm-step { display: flex; gap: 16px; align-items: flex-start; padding: 20px; border-top: 1px solid var(--gray-200); }
        .cvh-adm-step:first-child { border-top: 0; }
        .cvh-adm-dot { width: 36px; height: 36px; border-radius: 50%; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; font-weight: bold; background: var(--gray-100); color: var(--gray-500); }
        .cvh-adm-dot.is-done { background: var(--accent); color: #fff; }
        .cvh-adm-dot.is-current { background: var(--primary); color: #fff; }
        .cvh-adm-dot.is-rejected { background: var(--danger); color: #fff; }
    </style>
@endpush

@php
    if (!$notRequired) {
        $formDone = empty($form) || !empty($admission->form_submission_id);
        $feeDone = in_array($admission->payment_status, ['paid', 'waived']);
        $currencySign = currencySign($currency);
    }
@endphp

@section("content")
    <main class="pb-120">
        <section class="products-lists-hero position-relative">
            <div class="products-lists-hero__mask"></div>
            <img src="/store/cvh/courses/bundle-hd4a_cover.jpg" class="img-cover" alt="Admission"/>
        </section>

        <div class="container">
            <div class="products-lists-header position-relative z-index-2">
                <div class="products-lists-header__mask z-index-5"></div>
                <div class="position-relative bg-white rounded-32 z-index-8 p-24 p-lg-32">
                    <div class="d-flex-center size-64 rounded-12 bg-primary-20">
                        <x-iconsax-bul-teacher class="icons text-primary" width="32px" height="32px"/>
                    </div>
                    <h1 class="font-24 font-weight-bold mt-16">Your admission</h1>
                    <div class="font-14 text-gray-500 mt-8">Complete these steps to start learning on CVH Africa.</div>
                </div>
            </div>

            @if(session('cvh_admission_status'))
                <div class="mt-24 p-16 rounded-12 font-14" style="background: rgba(1,112,255,.08); color: var(--primary);">{{ session('cvh_admission_status') }}</div>
            @endif

            @if($notRequired)
                <div class="bg-white rounded-24 mt-24 p-24" style="border: 1px solid var(--gray-200);">
                    <div class="font-16 font-weight-bold">No admission needed</div>
                    <div class="font-14 text-gray-500 mt-8">Your account already has full access. <a href="/classes" class="text-primary">Browse courses</a></div>
                </div>
            @else
                @if(!empty($instructions))
                    <div class="bg-white rounded-24 mt-24 p-24 font-14 text-gray-500" style="border: 1px solid var(--gray-200);">{!! nl2br(e($instructions)) !!}</div>
                @endif

                <div class="bg-white rounded-24 mt-24" style="border: 1px solid var(--gray-200);">
                    {{-- 1. Account --}}
                    <div class="cvh-adm-step">
                        <div class="cvh-adm-dot is-done">✓</div>
                        <div>
                            <div class="font-16 font-weight-bold">Create your account</div>
                            <div class="font-14 text-gray-500 mt-4">Done.</div>
                        </div>
                    </div>

                    {{-- 2. Application form --}}
                    @if(!empty($form))
                        <div class="cvh-adm-step">
                            <div class="cvh-adm-dot {{ $formDone ? 'is-done' : 'is-current' }}">{{ $formDone ? '✓' : '2' }}</div>
                            <div class="flex-grow-1">
                                <div class="font-16 font-weight-bold">Fill in the application form</div>
                                @if($formDone)
                                    <div class="font-14 text-gray-500 mt-4">Submitted. Thank you.</div>
                                @else
                                    <div class="font-14 text-gray-500 mt-4">Tell us about yourself and what you would like to learn.</div>
                                    <a href="/forms/{{ $form->url }}" class="btn btn-primary mt-12">Open the application form</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 3. Admission fee --}}
                    @if($admission->payment_status !== 'waived')
                        <div class="cvh-adm-step">
                            <div class="cvh-adm-dot {{ $feeDone ? 'is-done' : ($formDone ? 'is-current' : '') }}">{{ $feeDone ? '✓' : (empty($form) ? '2' : '3') }}</div>
                            <div class="flex-grow-1">
                                <div class="font-16 font-weight-bold">Pay the admission fee</div>
                                @if($feeDone)
                                    <div class="font-14 text-gray-500 mt-4">Paid{{ $admission->paid_at ? ' on ' . dateTimeFormat($admission->paid_at, 'j M Y') : '' }}{{ $admission->payment_reference ? ' (reference ' . $admission->payment_reference . ')' : '' }}.</div>
                                @else
                                    <div class="font-14 text-gray-500 mt-4">One-time fee: <strong>{{ $currencySign }}{{ number_format($fee, 2) }}</strong></div>
                                    @if(!$formDone)
                                        <div class="font-12 text-gray-500 mt-8">Please fill in the application form first.</div>
                                    @elseif($onlinePayment)
                                        <form action="/admission/pay" method="post" class="mt-12">
                                            {{ csrf_field() }}
                                            <button type="submit" class="btn btn-primary">Pay {{ $currencySign }}{{ number_format($fee, 2) }} with Paystack</button>
                                        </form>
                                        <div class="font-12 text-gray-500 mt-8">Card or M-PESA, depending on your Paystack options.</div>
                                    @else
                                        <div class="font-12 text-gray-500 mt-8">Online payment is not available yet. Please contact CVH Africa to pay your admission fee.</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- 4. Review --}}
                    <div class="cvh-adm-step">
                        @php $reviewDot = $admission->status === 'approved' ? 'is-done' : ($admission->status === 'rejected' ? 'is-rejected' : ($admission->status === 'under_review' ? 'is-current' : '')); @endphp
                        <div class="cvh-adm-dot {{ $reviewDot }}">{{ $admission->status === 'approved' ? '✓' : ($admission->status === 'rejected' ? '!' : '★') }}</div>
                        <div class="flex-grow-1">
                            <div class="font-16 font-weight-bold">Admission decision</div>
                            @if($admission->status === 'approved')
                                <div class="font-14 text-gray-500 mt-4">You're admitted. Welcome to CVH Africa!</div>
                                <a href="/classes" class="btn btn-primary mt-12">Browse courses</a>
                            @elseif($admission->status === 'rejected')
                                <div class="font-14 text-gray-500 mt-4">Your application was not approved.{{ !empty($admission->admin_note) ? ' ' . $admission->admin_note : '' }}</div>
                            @elseif($admission->status === 'under_review')
                                <div class="font-14 text-gray-500 mt-4">Your application is being reviewed. We'll notify you by email as soon as it's decided.</div>
                            @else
                                <div class="font-14 text-gray-500 mt-4">Complete the steps above and your application goes to CVH Africa for review.</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
