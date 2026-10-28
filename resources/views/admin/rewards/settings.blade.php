{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\RewardController@settings (saved by @storeSettings) --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.rewards_settings') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/rewards') }}">{{ trans('update.rewards') }}</a></div>
                <div class="breadcrumb-item">{{ trans('admin/main.settings') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <form action="{{ getAdminPanelUrl('/rewards/settings') }}" method="post">
                                {{ csrf_field() }}
                                <input type="hidden" name="locale" value="{{ \App\Models\Setting::$defaultSettingsLocale }}">

                                <div class="form-group custom-switches-stacked">
                                    <label class="custom-switch pl-0 d-flex align-items-center">
                                        <input type="hidden" name="value[status]" value="0">
                                        <input type="checkbox" name="value[status]" id="rewardsStatusSwitch" value="1" {{ (!empty($itemValue) and !empty($itemValue['status'])) ? 'checked="checked"' : '' }} class="custom-switch-input"/>
                                        <span class="custom-switch-indicator"></span>
                                        <label class="custom-switch-description mb-0 cursor-pointer" for="rewardsStatusSwitch">{{ trans('admin/main.active') }}</label>
                                    </label>
                                    <div class="text-gray-500 text-small">{{ trans('update.admin_reward_setting_active_hint') }}</div>
                                </div>

                                <div class="form-group custom-switches-stacked">
                                    <label class="custom-switch pl-0 d-flex align-items-center">
                                        <input type="hidden" name="value[exchangeable]" value="0">
                                        <input type="checkbox" name="value[exchangeable]" id="exchangeableSwitch" value="1" {{ (!empty($itemValue) and !empty($itemValue['exchangeable'])) ? 'checked="checked"' : '' }} class="custom-switch-input"/>
                                        <span class="custom-switch-indicator"></span>
                                        <label class="custom-switch-description mb-0 cursor-pointer" for="exchangeableSwitch">{{ trans('update.exchangeable') }}</label>
                                    </label>
                                    <div class="text-gray-500 text-small">{{ trans('update.admin_reward_setting_exchangeable_hint') }}</div>
                                </div>

                                <div id="exchangeableUnitInput" class="form-group {{ (!empty($itemValue) and !empty($itemValue['exchangeable'])) ? '' : 'd-none' }}">
                                    <label class="input-label">{{ trans('update.exchangeable_unit') }}</label>
                                    <input type="number" min="1" name="value[exchangeable_unit]" value="{{ $itemValue['exchangeable_unit'] ?? '' }}" class="form-control @error('exchangeable_unit') is-invalid @enderror"/>
                                    @error('exchangeable_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="text-gray-500 text-small mt-1">{{ trans('update.exchangeable_unit_hint') }}</div>
                                </div>

                                <div class="form-group">
                                    <label class="input-label">{{ trans('update.want_more_points_link') }}</label>
                                    <input type="text" name="value[want_more_points_link]" value="{{ $itemValue['want_more_points_link'] ?? '' }}" class="form-control" placeholder="/pages/reward_points_system"/>
                                    <div class="text-gray-500 text-small mt-1">{{ trans('update.want_more_points_link_hint') }}</div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">{{ trans('admin/main.save_change') }}</button>
                                </div>
                            </form>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded" style="background: #f7f9fc;">
                                <h6 class="font-14">How points work</h6>
                                <ul class="font-12 text-muted pl-3 mb-0">
                                    <li>When rewards are active, users get points for the actions listed under <a href="{{ getAdminPanelUrl('/rewards/items') }}">{{ trans('update.conditions') }}</a> (e.g. completing a course, passing a quiz, earning a certificate, registering).</li>
                                    <li>Learners see their points and the leaderboard in their dashboard under "{{ trans('update.rewards') }}".</li>
                                    <li>With "{{ trans('update.exchangeable') }}" on, learners can convert points to wallet credit at the conversion rate above.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script src="/assets/admin/js/parts/rewards_settings.min.js"></script>
@endpush
