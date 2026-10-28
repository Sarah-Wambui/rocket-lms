{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\SubscribesController@create/edit.
     The target section is the template's own include, driven by /assets/admin/js/parts/create_subscribe.min.js --}}
@extends('admin.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/vendors/summernote/summernote-bs4.min.css">
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ !empty($subscribe) ? trans('admin/pages/financial.edit_subscribe') : trans('admin/pages/financial.new_subscribe') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/financial/subscribes') }}">{{ trans('admin/pages/financial.subscribes') }}</a></div>
                <div class="breadcrumb-item">{{ !empty($subscribe) ? trans('admin/main.edit') : trans('admin/main.new') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ getAdminPanelUrl(!empty($subscribe) ? "/financial/subscribes/{$subscribe->id}/update" : '/financial/subscribes/store') }}" method="post">
                        {{ csrf_field() }}

                        <div class="row">
                            <div class="col-12 col-md-6">

                                @if(!empty(getGeneralSettings('content_translate')))
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('auth.language') }}</label>
                                        <select name="locale" class="form-control {{ !empty($subscribe) ? 'js-edit-content-locale' : '' }}">
                                            @foreach($userLanguages as $lang => $language)
                                                <option value="{{ $lang }}" @if(mb_strtolower(request()->get('locale', app()->getLocale())) == mb_strtolower($lang)) selected @endif>{{ $language }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="locale" value="{{ getDefaultLocale() }}">
                                @endif

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.title') }}</label>
                                    <input type="text" name="title" value="{{ !empty($subscribe) ? $subscribe->title : old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Monthly plan"/>
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.subtitle') }}</label>
                                    <input type="text" name="subtitle" value="{{ !empty($subscribe) ? $subscribe->subtitle : old('subtitle') }}" class="form-control @error('subtitle') is-invalid @enderror" placeholder="e.g. Unlimited access to all courses for 30 days"/>
                                    @error('subtitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.price') }} ({{ currencySign(getDefaultCurrency()) }})</label>
                                    <input type="number" step="0.01" min="0" name="price" value="{{ !empty($subscribe) ? $subscribe->price : old('price') }}" class="form-control @error('price') is-invalid @enderror"/>
                                    @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.days') }}</label>
                                    <input type="number" min="1" name="days" value="{{ !empty($subscribe) ? $subscribe->days : old('days') }}" class="form-control @error('days') is-invalid @enderror" placeholder="30 = monthly, 365 = annual"/>
                                    <div class="text-muted text-small mt-1">How long the plan lasts after purchase: 30 for a monthly plan, 365 for an annual plan.</div>
                                    @error('days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.usable_count') }}</label>
                                    <input type="number" min="0" name="usable_count" value="{{ !empty($subscribe) ? $subscribe->usable_count : old('usable_count', 0) }}" class="form-control @error('usable_count') is-invalid @enderror"/>
                                    <div class="text-muted text-small mt-1">How many courses a subscriber can enrol in with this plan (ignored when Unlimited Usage is on).</div>
                                    @error('usable_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group d-flex align-items-center">
                                    <input type="hidden" name="infinite_use" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="infinite_use" value="1" class="custom-control-input" id="infiniteUseSwitch" {{ ((!empty($subscribe) and $subscribe->infinite_use) or old('infinite_use') == '1') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="infiniteUseSwitch">{{ trans('update.infinite_use') }}</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.icon') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <button type="button" class="input-group-text admin-file-manager" data-input="icon" data-preview="holder">
                                                <i class="fa fa-upload"></i>
                                            </button>
                                        </div>
                                        <input type="text" name="icon" id="icon" value="{{ !empty($subscribe) ? $subscribe->icon : old('icon') }}" class="form-control @error('icon') is-invalid @enderror"/>
                                        <div class="input-group-append">
                                            <button type="button" class="input-group-text admin-file-view" data-input="icon">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                        @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="text-muted text-small mt-1">Image shown on the plan card on the pricing page.</div>
                                </div>

                                <div class="form-group d-flex align-items-center">
                                    <input type="hidden" name="is_popular" value="0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="is_popular" value="1" class="custom-control-input" id="isPopularSwitch" {{ ((!empty($subscribe) and $subscribe->is_popular) or old('is_popular') == '1') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="isPopularSwitch">{{ trans('admin/main.is_popular') }}</label>
                                    </div>
                                </div>

                                @include('admin.financial.subscribes.create.includes.target_products')
                            </div>
                        </div>

                        <div class="form-group mt-15">
                            <label class="input-label">{{ trans('public.description') }}</label>
                            <textarea name="description" class="summernote form-control @error('description') is-invalid @enderror">{!! !empty($subscribe) ? $subscribe->description : old('description') !!}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-30">
                            <button type="submit" class="btn btn-primary">{{ trans('admin/main.save_change') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script src="/assets/vendors/summernote/summernote-bs4.min.js"></script>
    <script src="/assets/admin/js/parts/create_subscribe.min.js"></script>
@endpush
