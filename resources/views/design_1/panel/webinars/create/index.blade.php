@extends('design_1.web.layouts.app', ['appFooter' => false])

@push('styles_top')
    <link rel="stylesheet" href="{{ getDesign1StylePath("create-course") }}">

    @if(!empty($singlePage))
        <link rel="stylesheet" href="/assets/vendors/summernote/summernote-bs4.min.css">
        <link rel="stylesheet" href="/assets/default/vendors/daterangepicker/daterangepicker.min.css">
        <link rel="stylesheet" href="/assets/default/vendors/bootstrap-tagsinput/bootstrap-tagsinput.min.css">
        <link rel="stylesheet" href="/assets/default/vendors/sortable/jquery-ui.min.css"/>
    @endif
@endpush

@section('content')
    <form method="post" action="/panel/courses/{{ !empty($webinar) ? $webinar->id .'/update' : 'store' }}" id="webinarForm" enctype="multipart/form-data">
        {{ csrf_field() }}
        <input type="hidden" name="current_step" value="{{ !empty($currentStep) ? $currentStep : 1 }}">
        <input type="hidden" name="draft" value="no" id="forDraft"/>
        <input type="hidden" name="get_next" value="no" id="getNext"/>
        <input type="hidden" name="get_step" value="0" id="getStep"/>


        <div class="container mt-80 pb-100">
            @if(!empty($singlePage))
                {{-- CVH: one-page edit form, like the admin edit page: every section below, one save --}}
                @php
                    $cvhSections = [
                        1 => 'basic_information',
                        2 => 'extra_information',
                        3 => 'pricing',
                        4 => 'content',
                        5 => 'prerequisites',
                        6 => 'faq',
                        7 => 'quiz_certificate',
                    ];

                    if (empty(getGeneralOptionsSettings('direct_publication_of_courses'))) {
                        $cvhSections[8] = 'message_to_reviewer';
                    }
                @endphp

                <h1 class="font-24 font-weight-bold">{{ trans('public.edit') }}: {{ $webinar->title }}</h1>

                @foreach($cvhSections as $cvhStep => $cvhSectionName)
                    <section id="section-{{ $cvhSectionName }}" class="mt-40">
                        <h2 class="font-20 font-weight-bold">{{ trans('public.' . $cvhSectionName) }}</h2>

                        @include("design_1.panel.webinars.create.steps.step_{$cvhStep}")
                    </section>
                @endforeach
            @else
                {{-- Progress --}}
                @include('design_1.panel.webinars.create.includes.progress')

                {{-- Steps Inputs --}}
                @include("design_1.panel.webinars.create.steps.step_{$currentStep}")
            @endif
        </div>


        {{-- Bottom Actions --}}
        @include('design_1.panel.webinars.create.includes.bottom_actions')

    </form>
@endsection

@push('scripts_bottom')
    <script>
        var saveSuccessLang = '{{ trans('webinars.success_store') }}';
        var zoomJwtTokenInvalid = '{{ trans('webinars.zoom_jwt_token_invalid') }}';
        var hasZoomApiToken = '{{ (!empty(getFeaturesSettings('zoom_client_id')) and !empty(getFeaturesSettings('zoom_client_secret'))) ? 'true' : 'false' }}';
        var editChapterLang = '{{ trans('public.edit_chapter') }}';
    </script>

    @if(!empty($singlePage))
        <script>
            var newChapterLang = '{{ trans('public.new_chapter') }}';
            var saveLang = '{{ trans('public.save') }}';
            var closeLang = '{{ trans('public.close') }}';
            var quizzesSectionLang = '{{ trans('quiz.quizzes_section') }}';
            var newQuestionLang = '{{ trans('update.new_question') }}';
            var editQuestionLang = '{{ trans('update.edit_question') }}';
            var changeChapterLang = '{{ trans('update.change_chapter') }}';

            // CVH: the sections' own add/edit forms are saved separately (ajax); keep them out of the main save
            $('#webinarForm').on('submit', function () {
                $(this).find('[name^="ajax["]').prop('disabled', true);
            });
            window.addEventListener('pageshow', function () {
                $('#webinarForm').find('[name^="ajax["]').prop('disabled', false);
            });
        </script>

        <script src="/assets/vendors/summernote/summernote-bs4.min.js"></script>
        <script src="/assets/default/vendors/moment.min.js"></script>
        <script src="/assets/default/vendors/daterangepicker/daterangepicker.min.js"></script>
        <script src="/assets/default/vendors/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
        <script src="/assets/default/vendors/sortable/jquery-ui.min.js"></script>
        <script src="/assets/design_1/js/panel/quiz_create.min.js"></script>
    @endif

    <script src="/assets/design_1/js/panel/create_webinar.min.js"></script>
    <script src="/assets/design_1/js/panel/webinar_content_locale.min.js"></script>
@endpush
