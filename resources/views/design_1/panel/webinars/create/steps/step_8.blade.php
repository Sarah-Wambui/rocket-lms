@if(empty($singlePage)) {{-- CVH: the one-page edit form loads these once (create/index.blade.php) --}}
@push('styles_top')

@endpush
@endif

<div class="bg-white rounded-16 p-16 mt-32">
    <h3 class="font-14 font-weight-bold">{{ trans('public.message_to_reviewer') }}</h3>


    <div class="form-group mt-24">
        <label class="form-group-label">{{ trans('site.message') }}</label>
        <textarea name="message_for_reviewer" rows="10" class="form-control">{{ (!empty($webinar) and $webinar->message_for_reviewer) ? $webinar->message_for_reviewer : old('message_for_reviewer') }}</textarea>
    </div>

    {{-- CVH: the "I agree with the rules" switch is removed; sending for review no longer needs it (Panel\WebinarController::update) --}}

    @php
        $contentReviewInformationSetting = getContentReviewInformationSettings();
    @endphp

    @if(!empty($contentReviewInformationSetting))
        <div class="bg-gray-100 mt-16 p-16 rounded-16 border-gray-300">
            <div class="row">
                <div class="col-12 col-lg-4">
                    <h4 class="font-14 mb-12">{{ trans('update.tips_and_policies') }}</h4>

                    @if(!empty($contentReviewInformationSetting['description']))
                        <div class="text-gray-500">{!! nl2br($contentReviewInformationSetting['description']) !!}</div>
                    @endif
                </div>

                <div class="col-lg-4"></div>
                <div class="col-12 col-lg-4 mt-16 mt-lg-0">
                    @if(!empty($contentReviewInformationSetting['image']))
                        <img src="{{ $contentReviewInformationSetting['image'] }}" alt="{{ trans('update.tips_and_policies') }}" class="img-fluid w-100">
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

@if(empty($singlePage)) {{-- CVH: the one-page edit form loads these once (create/index.blade.php) --}}
@push('scripts_bottom')

@endpush
@endif
