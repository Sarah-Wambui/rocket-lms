{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\UpcomingCoursesController@create/edit.
     The sections are the template's own includes; FAQ / learning-material / logo items and the save buttons are handled by
     /assets/admin/js/parts/create_upcoming_course.min.js --}}
@extends('admin.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/default/vendors/bootstrap-tagsinput/bootstrap-tagsinput.min.css">
    <link rel="stylesheet" href="/assets/vendors/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="/assets/default/vendors/sortable/jquery-ui.min.css"/>
    <style>
        .select2-container {
            z-index: 1212 !important;
        }
    </style>
@endpush

@php
    $upcomingCourse = $upcomingCourse ?? null;
    $upcomingCourseCategoryFilters = $upcomingCourseCategoryFilters ?? [];
    $upcomingCourseTags = $upcomingCourseTags ?? [];
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ !empty($upcomingCourse) ? trans('admin/main.edit') : trans('admin/main.new') }} {{ trans('update.upcoming_course') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl('/upcoming_courses') }}">{{ trans('update.upcoming_courses') }}</a></div>
                <div class="breadcrumb-item">{{ !empty($upcomingCourse) ? trans('admin/main.edit') : trans('admin/main.new') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @if(!empty($upcomingCourse))
                                <div class="d-flex flex-wrap align-items-center mb-20 gap-12 text-small">
                                    <span>{{ trans('admin/main.status') }}:
                                        <strong>{{ trans('admin/main.' . ($upcomingCourse->status == 'active' ? 'published' : $upcomingCourse->status)) }}</strong>
                                    </span>
                                    <a href="{{ $upcomingCourse->getUrl() }}" target="_blank" class="ml-3">{{ trans('update.preview') }}</a>
                                    @can('admin_upcoming_courses_followers')
                                        <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/followers") }}" class="ml-3">{{ trans('update.followers') }}</a>
                                    @endcan
                                </div>
                            @endif

                            <form method="post" action="{{ getAdminPanelUrl('/upcoming_courses/' . (!empty($upcomingCourse) ? $upcomingCourse->id . '/update' : 'store')) }}" id="upcomingCourseForm" class="webinar-form" enctype="multipart/form-data">
                                {{ csrf_field() }}

                                <section>
                                    <h2 class="section-title after-line">{{ trans('public.basic_information') }}</h2>
                                    @include('admin.upcoming_courses.create.includes.basic_information')
                                </section>

                                <section class="mt-3">
                                    <h2 class="section-title after-line">{{ trans('public.additional_information') }}</h2>
                                    @include('admin.upcoming_courses.create.includes.additional_information')
                                </section>

                                @if(!empty($upcomingCourse))
                                    @include('admin.upcoming_courses.create.includes.faq')
                                    @include('admin.upcoming_courses.create.includes.extraDescription')
                                @endif

                                <section class="mt-3">
                                    <h2 class="section-title after-line">{{ trans('public.message_to_reviewer') }}</h2>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group mt-15">
                                                <textarea name="message_for_reviewer" rows="6" class="form-control">{{ (!empty($upcomingCourse) and $upcomingCourse->message_for_reviewer) ? $upcomingCourse->message_for_reviewer : old('message_for_reviewer') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <input type="hidden" name="draft" value="no" id="forDraft"/>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" id="saveAndPublish" class="btn btn-success">{{ !empty($upcomingCourse) ? trans('admin/main.save_and_publish') : trans('admin/main.save_and_continue') }}</button>

                                        @if(!empty($upcomingCourse))
                                            <button type="button" id="saveAsDraft" class="btn btn-primary">{{ trans('public.save_as_draft') }}</button>

                                            <button type="button" id="saveReject" class="btn btn-warning">{{ ($upcomingCourse->status == \App\Models\UpcomingCourse::$active) ? trans('admin/main.unpublish') : trans('public.reject') }}</button>

                                            @can('admin_upcoming_courses_delete')
                                                @include('admin.includes.delete_button', [
                                                    'url' => getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/delete"),
                                                    'btnText' => trans('public.delete'),
                                                    'hideDefaultClass' => true,
                                                    'noBtnTransparent' => true,
                                                    'btnClass' => 'btn btn-danger d-inline-flex',
                                                ])
                                            @endcan
                                        @endif
                                    </div>
                                </div>
                            </form>

                            @include('admin.upcoming_courses.create.includes.modals.faq')
                            @include('admin.upcoming_courses.create.includes.modals.extra_description')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script>
        var saveSuccessLang = '{{ trans('webinars.success_store') }}';
    </script>

    <script src="/assets/default/vendors/feather-icons/dist/feather.min.js"></script>
    <script src="/assets/default/vendors/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
    <script src="/assets/vendors/summernote/summernote-bs4.min.js"></script>
    <script src="/assets/default/vendors/sortable/jquery-ui.min.js"></script>
    <script src="/assets/admin/js/parts/create_upcoming_course.min.js"></script>
@endpush
