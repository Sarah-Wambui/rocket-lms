{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\RewardController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.rewards') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.rewards') }}</div>
                <div class="breadcrumb-item">{{ trans('public.history') }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(empty(getRewardsSettings()['status']))
                <div class="alert alert-warning">
                    Reward points are switched off, so no points are being given.
                    @can('admin_rewards_settings')
                        <a href="{{ getAdminPanelUrl('/rewards/settings') }}" class="text-white font-weight-bold" style="text-decoration: underline;">{{ trans('update.rewards_settings') }}</a>
                    @endcan
                </div>
            @endif

            <div class="card">
                <div class="card-header justify-content-between">
                    <div class="text-muted font-14">{{ $rewards->total() }} {{ trans('admin/main.users') }}</div>
                    <div>
                        @can('admin_rewards_items')
                            <a href="{{ getAdminPanelUrl('/rewards/items') }}" class="btn btn-outline-primary">{{ trans('update.conditions') }}</a>
                        @endcan
                        @can('admin_rewards_settings')
                            <a href="{{ getAdminPanelUrl('/rewards/settings') }}" class="btn btn-outline-primary ml-1">{{ trans('admin/main.settings') }}</a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.user') }}</th>
                                <th>{{ trans('update.total_points') }}</th>
                                <th>{{ trans('update.spent_points') }}</th>
                                <th>{{ trans('update.available_points') }}</th>
                            </tr>

                            @forelse($rewards as $reward)
                                <tr>
                                    <td class="text-left">
                                        @if(!empty($reward->user))
                                            <div class="d-flex align-items-center">
                                                <figure class="avatar mr-2">
                                                    <img src="{{ $reward->user->getAvatar() }}" alt="{{ $reward->user->full_name }}">
                                                </figure>
                                                <div class="media-body ml-1">
                                                    <div class="mt-0 mb-1 font-weight-bold">{{ $reward->user->full_name ?: '-' }}</div>
                                                    <div class="text-primary text-small font-600-bold">{{ $reward->user->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">#{{ $reward->user_id }}</span>
                                        @endif
                                    </td>
                                    <td>{{ (int)$reward->total_points }}</td>
                                    <td>{{ (int)$reward->spent_points }}</td>
                                    <td class="font-weight-bold">{{ (int)$reward->available_points }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-30">{{ trans('update.reward_no_result') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $rewards->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
