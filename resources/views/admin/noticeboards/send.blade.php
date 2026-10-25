{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\NoticeboardController@create/edit
     (site notices) and Admin\CourseNoticeboardController@create/edit (course notices, $isCourseNotice = true) --}}
@extends('admin.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/vendors/summernote/summernote-bs4.min.css">
@endpush

@php
    $isCourseNotice = !empty($isCourseNotice);
    $basePath = $isCourseNotice ? '/course-noticeboards' : '/noticeboards';
    $action = getAdminPanelUrl($basePath . (!empty($noticeboard) ? '/' . $noticeboard->id . '/update' : '/store'));
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ !empty($noticeboard) ? trans('admin/main.edit_noticeboard') : trans('admin/main.new_notice_title') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl($basePath) }}">{{ $isCourseNotice ? trans('update.course_notices') : trans('panel.noticeboards') }}</a></div>
                <div class="breadcrumb-item">{{ !empty($noticeboard) ? trans('admin/main.edit_noticeboard') : trans('admin/main.new_notice_title') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ $action }}" method="post">
                        {{ csrf_field() }}

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.title') }}</label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ !empty($noticeboard) ? $noticeboard->title : old('title') }}">
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($isCourseNotice)
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.course') }}</label>
                                        <select name="webinar_id" class="form-control search-webinar-select2 @error('webinar_id') is-invalid @enderror" data-placeholder="{{ trans('update.select_a_course') }}">
                                            @php
                                                $selectedWebinar = !empty($noticeboard) ? $noticeboard->webinar : (!empty(old('webinar_id')) ? \App\Models\Webinar::find(old('webinar_id')) : null);
                                            @endphp
                                            @if(!empty($selectedWebinar))
                                                <option value="{{ $selectedWebinar->id }}" selected>{{ $selectedWebinar->title }}</option>
                                            @endif
                                        </select>
                                        @error('webinar_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="text-gray-500 text-small mt-1">{{ trans('update.the_notice_will_be_send_on_the_selected_target') }}</div>
                                    </div>

                                    <div class="form-group">
                                        <label class="input-label">{{ trans('update.color') }}</label>
                                        <select name="color" class="form-control @error('color') is-invalid @enderror">
                                            <option value="">{{ trans('update.select_a_color') }}</option>
                                            @foreach(\App\Models\CourseNoticeboard::$colors as $color)
                                                <option value="{{ $color }}" {{ ((!empty($noticeboard) and $noticeboard->color == $color) or old('color') == $color) ? 'selected' : '' }}>{{ trans('update.course_noticeboard_color_' . $color) }}</option>
                                            @endforeach
                                        </select>
                                        @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.type') }}</label>
                                        <select name="type" class="form-control @error('type') is-invalid @enderror">
                                            <option value="">{{ trans('admin/main.select_type') }}</option>
                                            @foreach(\App\Models\Noticeboard::$adminTypes as $type)
                                                <option value="{{ $type }}" {{ ((!empty($noticeboard) and $noticeboard->type == $type) or old('type') == $type) ? 'selected' : '' }}>{{ $type === 'organizations' ? trans('admin/main.organizations') : trans('public.' . $type) }}</option>
                                            @endforeach
                                        </select>
                                        @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="text-gray-500 text-small mt-1">{{ trans('admin/main.new_noticeboards_hint') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="input-label">{{ trans('site.message') }}</label>
                            <textarea name="message" class="summernote form-control @error('message') is-invalid @enderror" data-height="300">{!! !empty($noticeboard) ? $noticeboard->message : old('message') !!}</textarea>
                            @error('message')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary">{{ trans('admin/main.send_noticeboard') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script src="/assets/vendors/summernote/summernote-bs4.min.js"></script>
@endpush
