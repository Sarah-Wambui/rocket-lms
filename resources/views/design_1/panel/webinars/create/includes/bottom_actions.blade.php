<div class="position-fixed position-bottom-0 position-left-0 position-right-0 z-index-3 bg-white soft-shadow-2">
    <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between py-16">

        {{-- CVH: the one-page edit form has no previous/next steps --}}
        <div class="{{ !empty($singlePage) ? 'd-none' : 'd-flex' }} align-items-center">
            @if(empty($singlePage))
            {{-- Previous --}}
            <a href="{{ (!empty($webinar) and $currentStep > 1) ? ("/panel/courses/{$webinar->id}/step/" . ($currentStep - 1)) : '#!' }}" class="d-flex-center size-48 rounded-circle bg-gray-100">
                @svg("iconsax-lin-arrow-left", ['height' => 16, 'width' => 16, 'class' => (!empty($webinar) and $currentStep > 1) ? 'text-primary' : 'text-gray-500'])
            </a>

            {{-- Next --}}
            <div id="getNextStep" class="d-flex-center size-48 rounded-circle bg-gray-100 ml-16 cursor-pointer">
                @svg("iconsax-lin-arrow-right", ['height' => 16, 'width' => 16, 'class' => ($currentStep < $stepCount) ? 'text-primary' : 'text-gray-500'])
            </div>
            @endif

        </div>

        <div class="d-flex align-items-center {{ !empty($singlePage) ? 'ml-lg-auto' : '' }} mt-20 mt-lg-0 gap-8">
            {{-- Save as Draft --}}
            <button type="button" id="saveAsDraft" class=" btn btn-transparent text-gray-500">{{ trans('public.save_as_draft') }}</button>

            @if(!empty($webinar) and $webinar->creator_id == $authUser->id)
                @include('design_1.panel.includes.content_delete_btn', [
                    'deleteContentUrl' => "/panel/courses/{$webinar->id}/delete?redirect_to=/panel/courses",
                    'deleteContentClassName' => 'webinar-actions text-danger ml-16',
                    'deleteContentItem' => $webinar,
                    'deleteContentItemType' => "course",
                ])
            @endif

            {{-- Send for Review --}}
            <button type="button" id="sendForReview" class="btn btn-lg btn-primary ml-16">{{ !empty(getGeneralOptionsSettings('direct_publication_of_courses')) ? trans('update.publish') : trans('public.send_for_review') }}</button>




        </div>
    </div>

    @if(empty($singlePage))
        @php
            $stepProgressPercent = (($currentStep * 100) / $stepCount);
        @endphp

        <div class="create-course-bottom-progress">
            <div class="create-course-bottom-progress__process" style="width: {{ $stepProgressPercent }}%"></div>
        </div>
    @endif
</div>
