{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\FormsController@create/edit.
     The tab contents are the template's own includes; field editing is done by /assets/admin/js/parts/create_forms.min.js --}}
@extends('admin.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/vendors/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="/assets/default/vendors/sortable/jquery-ui.min.css"/>
@endpush

@php
    $defaultLocale = getDefaultLocale();
    $definedLanguage = !empty($form) ? $form->translations->pluck('locale')->toArray() : [];
    $activeTab = old('active_tab', request()->get('tab', 'basic'));
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ !empty($form) ? trans('update.edit_form') : trans('update.new_form') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/forms') }}">{{ trans('update.forms') }}</a></div>
                <div class="breadcrumb-item">{{ !empty($form) ? trans('update.edit_form') : trans('update.new_form') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">

                    <ul class="nav nav-pills" id="formTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'basic' ? 'active' : '' }}" data-toggle="tab" href="#basicTab" role="tab">{{ trans('public.basic_information') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'welcome' ? 'active' : '' }}" data-toggle="tab" href="#welcomeTab" role="tab">{{ trans('update.welcome_message') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'thanks' ? 'active' : '' }}" data-toggle="tab" href="#thanksTab" role="tab">{{ trans('update.tank_you_message') }}</a>
                        </li>
                        @if(!empty($form))
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab == 'fields' ? 'active' : '' }}" data-toggle="tab" href="#fieldsTab" role="tab">{{ trans('update.form_fields') }} ({{ count($form->fields) }})</a>
                            </li>
                        @endif
                    </ul>

                    <div class="tab-content mt-30" id="formTabsContent">
                        <form action="{{ getAdminPanelUrl(!empty($form) ? "/forms/{$form->id}/update" : '/forms/store') }}" method="post" id="formMainForm">
                            {{ csrf_field() }}

                            <div class="tab-pane fade {{ $activeTab == 'basic' ? 'show active' : '' }}" id="basicTab" role="tabpanel">
                                @include('admin.forms.create.includes.basic_information')

                                @if(!empty($form))
                                    <div class="form-group d-flex align-items-center mt-20">
                                        <label class="mb-0" for="enableFormSwitch">{{ trans('update.enable') }}</label>
                                        <div class="custom-control custom-switch ml-3">
                                            <input type="checkbox" name="enable" class="custom-control-input" id="enableFormSwitch" {{ $form->enable ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="enableFormSwitch"></label>
                                        </div>
                                    </div>
                                    <div class="text-muted text-small">Visitors can open the form at <a href="/forms/{{ $form->url }}" target="_blank">{{ url('/forms/' . $form->url) }}</a> only while it is enabled.</div>
                                @else
                                    <div class="text-muted text-small mt-20">After saving, add the form inputs in the "{{ trans('update.form_fields') }}" tab, then enable the form.</div>
                                @endif
                            </div>

                            <div class="tab-pane fade {{ $activeTab == 'welcome' ? 'show active' : '' }}" id="welcomeTab" role="tabpanel">
                                @include('admin.forms.create.includes.welcome_message')
                            </div>

                            <div class="tab-pane fade {{ $activeTab == 'thanks' ? 'show active' : '' }}" id="thanksTab" role="tabpanel">
                                @include('admin.forms.create.includes.tank_you_message')
                            </div>

                            <div class="js-main-form-actions mt-30 {{ $activeTab == 'fields' ? 'd-none' : '' }}">
                                <button type="submit" class="btn btn-primary">{{ trans('admin/main.save_change') }}</button>
                            </div>
                        </form>

                        @if(!empty($form))
                            <div class="tab-pane fade {{ $activeTab == 'fields' ? 'show active' : '' }}" id="fieldsTab" role="tabpanel">
                                @include('admin.forms.create.includes.form_fields')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script>
        var saveSuccessLang = '{{ trans('webinars.success_store') }}';
        var requestFailedLang = '{{ trans('public.request_failed') }}';
        var titleLang = '{{ trans('admin/main.title') }}';
    </script>

    <script src="/assets/default/vendors/feather-icons/dist/feather.min.js"></script>
    <script src="/assets/vendors/summernote/summernote-bs4.min.js"></script>
    <script src="/assets/default/vendors/sortable/jquery-ui.min.js"></script>
    <script src="/assets/admin/js/parts/create_forms.min.js"></script>

    <script>
        (function ($) {
            "use strict";

            // The first three panes sit inside the save <form> and the inputs pane does not, so Bootstrap cannot
            // switch them as siblings: hide every pane before the chosen one is shown.
            $('#formTabs a[data-toggle="tab"]').on('show.bs.tab', function () {
                $('#formTabsContent .tab-pane').removeClass('show active');
            });

            // The fields tab is edited with its own ajax buttons, so hide the main Save button there
            $('#formTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $('.js-main-form-actions').toggleClass('d-none', $(e.target).attr('href') === '#fieldsTab');
            });

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        })(jQuery);
    </script>
@endpush
