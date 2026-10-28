{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\RewardController@create.
     Add/edit happens in the modal below, driven by /assets/admin/js/parts/rewards_items.min.js (POST /rewards/items) --}}
@extends('admin.layouts.app')

@php
    $rewardTypeLabel = function ($type) {
        $key = 'update.reward_type_' . $type;
        $label = trans($key);
        return $label === $key ? \Illuminate\Support\Str::headline($type) : $label;
    };
    $conditionTypes = ['charge_wallet', 'account_charge', 'buy', 'buy_store_product'];
    $conditionHints = [
        'buy' => 'Points are given per this amount spent (e.g. condition 10 and 50 points = 50 points for every 10 spent).',
        'account_charge' => 'Points are given per this amount charged to the account.',
        'buy_store_product' => 'Points are given per this amount spent in the store.',
        'charge_wallet' => 'Points are given when a wallet top-up is larger than this amount.',
    ];
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.rewards') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/rewards') }}">{{ trans('update.rewards') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.conditions') }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(empty(getRewardsSettings()['status']))
                <div class="alert alert-warning">
                    Reward points are switched off: these conditions only give points once rewards are enabled.
                    @can('admin_rewards_settings')
                        <a href="{{ getAdminPanelUrl('/rewards/settings') }}" class="text-white font-weight-bold" style="text-decoration: underline;">{{ trans('update.rewards_settings') }}</a>
                    @endcan
                </div>
            @endif

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">{{ trans('update.conditions') }}</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">Each condition gives learners/instructors points when they do that action. Only one condition per action is used.</p>
                    </div>
                    <button type="button" class="js-add-new-reward btn btn-primary">{{ trans('update.new_condition') }}</button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.type') }}</th>
                                <th>{{ trans('update.score') }}</th>
                                <th>{{ trans('update.condition') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($rewards as $reward)
                                <tr>
                                    <td class="text-left">{{ $rewardTypeLabel($reward->type) }}</td>
                                    <td>{{ $reward->type === 'badge' ? 'Badge points' : $reward->score }}</td>
                                    <td>{{ in_array($reward->type, $conditionTypes) ? $reward->condition : '-' }}</td>
                                    <td>
                                        @if($reward->status === 'active')
                                            <span class="text-success">{{ trans('admin/main.active') }}</span>
                                        @else
                                            <span class="text-warning">{{ trans('public.disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="js-edit-reward btn-transparent btn-sm text-primary mr-1" data-id="{{ $reward->id }}" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.edit') }}">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        @can('admin_rewards_item_delete')
                                            @include('admin.includes.delete_button',['url' => getAdminPanelUrl("/rewards/items/{$reward->id}/delete"), 'btnClass' => 'btn-sm'])
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-30">{{ trans('update.conditions') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Add / edit condition --}}
    <div class="modal fade" id="rewardSettingModal" tabindex="-1" aria-labelledby="rewardSettingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rewardSettingModalLabel">{{ trans('update.new_condition') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label class="input-label">{{ trans('admin/main.type') }}</label>
                            <select name="type" class="form-control">
                                <option value="">{{ trans('admin/main.type') }}</option>
                                @foreach(\App\Models\Reward::getTypesLists() as $type)
                                    <option value="{{ $type }}">{{ $rewardTypeLabel($type) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group js-score-input">
                            <label class="input-label">{{ trans('update.score') }}</label>
                            <input type="number" min="0" name="score" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-group js-condition-input d-none">
                            <label class="input-label">{{ trans('update.condition') }}</label>
                            <input type="number" min="0" step="any" name="condition" class="form-control">
                            <div class="invalid-feedback"></div>
                            <div class="text-muted text-small mt-1 js-condition-hint"></div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="status" class="custom-control-input" id="rewardStatusSwitch">
                                <label class="custom-control-label" for="rewardStatusSwitch">{{ trans('admin/main.active') }}</label>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('admin/main.cancel') }}</button>
                    <button type="button" class="js-save-reward btn btn-primary">{{ trans('public.save') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts_bottom')
    <script>
        var saveSuccessLang = '{{ trans('webinars.success_store') }}';
    </script>
    <script src="/assets/admin/js/parts/rewards_items.min.js"></script>
    <script>
        (function ($) {
            "use strict";

            var conditionHints = @json($conditionHints);
            var $modal = $('#rewardSettingModal');

            function showConditionHint(type) {
                $modal.find('.js-condition-hint').text(conditionHints[type] || '');
            }

            // "New condition" opens an empty form (the template script only resets it when editing)
            $('body').on('click', '.js-add-new-reward', function () {
                $modal.find('input[name="reward_id"]').remove();
                $modal.find('form')[0].reset();
                $modal.find('.is-invalid').removeClass('is-invalid');
                $modal.find('.js-score-input').removeClass('d-none');
                $modal.find('.js-condition-input').addClass('d-none');
                showConditionHint('');
            });

            $('body').on('change', '#rewardSettingModal select[name="type"]', function () {
                showConditionHint($(this).val());
            });

            $modal.on('shown.bs.modal', function () {
                showConditionHint($modal.find('select[name="type"]').val());
            });
        })(jQuery);
    </script>
@endpush
