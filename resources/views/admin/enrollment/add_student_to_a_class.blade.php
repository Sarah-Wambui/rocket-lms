{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); posts to Admin\EnrollmentController@store
     (free manual enrolment of a user into a course or bundle; on success it redirects to the enrolment history). --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.add_student_to_a_class') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/enrollments/history') }}">{{ trans('update.enrollment') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.add_student_to_a_class') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ getAdminPanelUrl('/enrollments/store') }}" method="post">
                        {{ csrf_field() }}

                        <div class="row">
                            <div class="col-12 col-md-6 col-lg-5">

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.user') }}</label>
                                    <select name="user_id" class="form-control search-user-select2 @error('user_id') is-invalid @enderror" data-placeholder="{{ trans('public.search_user') }}">
                                        @if(!empty(old('user_id')) and $oldUser = \App\User::find(old('user_id')))
                                            <option value="{{ $oldUser->id }}" selected>{{ $oldUser->full_name }}</option>
                                        @endif
                                    </select>
                                    @error('user_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.type') }}</label>
                                    <select class="form-control js-enrollment-item-type">
                                        <option value="webinar" {{ empty(old('bundle_id')) ? 'selected' : '' }}>{{ trans('admin/main.course') }}</option>
                                        <option value="bundle" {{ !empty(old('bundle_id')) ? 'selected' : '' }}>{{ trans('update.bundle') }}</option>
                                    </select>
                                </div>

                                <div class="form-group js-enrollment-item js-enrollment-item-webinar {{ !empty(old('bundle_id')) ? 'd-none' : '' }}">
                                    <label class="input-label">{{ trans('admin/main.course') }}</label>
                                    <select name="webinar_id" class="form-control search-webinar-select2 @error('webinar_id') is-invalid @enderror" data-placeholder="{{ trans('admin/main.search_webinar') }}">
                                        @if(!empty(old('webinar_id')) and $oldWebinar = \App\Models\Webinar::find(old('webinar_id')))
                                            <option value="{{ $oldWebinar->id }}" selected>{{ $oldWebinar->title }}</option>
                                        @endif
                                    </select>
                                    @error('webinar_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group js-enrollment-item js-enrollment-item-bundle {{ empty(old('bundle_id')) ? 'd-none' : '' }}">
                                    <label class="input-label">{{ trans('update.bundle') }}</label>
                                    <select name="bundle_id" class="form-control search-bundle-select2 @error('bundle_id') is-invalid @enderror" data-placeholder="{{ trans('update.search_bundle') }}">
                                        @if(!empty(old('bundle_id')) and $oldBundle = \App\Models\Bundle::find(old('bundle_id')))
                                            <option value="{{ $oldBundle->id }}" selected>{{ $oldBundle->title }}</option>
                                        @endif
                                    </select>
                                    @error('bundle_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="text-muted text-small mb-3">The learner gets free access immediately; the enrolment appears in the history list.</div>

                                <button type="submit" class="btn btn-primary">{{ trans('admin/main.submit') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script>
        (function ($) {
            "use strict";

            // Only the selected item type is sent (the store action uses the first of webinar_id / bundle_id that is filled)
            $('body').on('change', '.js-enrollment-item-type', function () {
                var type = $(this).val();
                $('.js-enrollment-item').addClass('d-none').find('select').val(null).trigger('change');
                $('.js-enrollment-item-' + type).removeClass('d-none');
            });
        })(jQuery);
    </script>
@endpush
