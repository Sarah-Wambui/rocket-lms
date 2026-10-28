{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\SubscribesController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('admin/pages/financial.subscribes') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('admin/pages/financial.subscribes') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header justify-content-between">
                    <div class="text-muted font-14">{{ $subscribes->total() }} {{ trans('admin/pages/financial.subscribes') }}</div>
                    @can('admin_subscribe_create')
                        <a href="{{ getAdminPanelUrl('/financial/subscribes/new') }}" class="btn btn-primary">{{ trans('admin/pages/financial.new_subscribe') }}</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                <th>{{ trans('admin/main.price') }}</th>
                                <th>{{ trans('admin/main.days') }}</th>
                                <th>{{ trans('admin/main.usable_count') }}</th>
                                <th>{{ trans('update.target_types') }}</th>
                                <th>{{ trans('admin/main.sales') }}</th>
                                <th>{{ trans('admin/main.income') }}</th>
                                <th>{{ trans('admin/main.created_at') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($subscribes as $subscribe)
                                <tr>
                                    <td class="text-left">
                                        <div class="d-flex align-items-center">
                                            @if(!empty($subscribe->icon))
                                                <img src="{{ $subscribe->icon }}" alt="" width="36" height="36" class="mr-2" style="object-fit: contain;">
                                            @endif
                                            <div>
                                                <div class="font-weight-bold">
                                                    {{ $subscribe->title }}
                                                    @if($subscribe->is_popular)
                                                        <span class="badge badge-primary ml-1">{{ trans('admin/main.is_popular') }}</span>
                                                    @endif
                                                </div>
                                                @if(!empty($subscribe->subtitle))
                                                    <div class="text-muted font-12">{{ truncate($subscribe->subtitle, 60) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $subscribe->price > 0 ? handlePrice($subscribe->price) : trans('public.free') }}</td>
                                    <td>{{ $subscribe->days }}</td>
                                    <td>{{ $subscribe->infinite_use ? trans('update.infinite_use') : $subscribe->usable_count }}</td>
                                    <td>
                                        {{ trans('update.target_types_' . $subscribe->target_type) }}
                                        @if(!empty($subscribe->target) and !in_array($subscribe->target, ['all_courses', 'all_bundles']))
                                            <div class="text-muted font-12">{{ trans('update.' . $subscribe->target) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $subscribe->sales->where('type', 'subscribe')->count() }}</td>{{-- plan purchases only; enrolments made with the plan also carry subscribe_id --}}
                                    <td>{{ handlePrice($subscribe->sales->where('type', 'subscribe')->sum('total_amount')) }}</td>
                                    <td>{{ dateTimeFormat($subscribe->created_at, 'j M Y') }}</td>
                                    <td>
                                        @can('admin_subscribe_edit')
                                            <a href="{{ getAdminPanelUrl("/financial/subscribes/{$subscribe->id}/edit") }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('admin_subscribe_delete')
                                            @include('admin.includes.delete_button',['url' => getAdminPanelUrl("/financial/subscribes/{$subscribe->id}/delete"), 'btnClass' => 'btn-sm'])
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-30">{{ trans('admin/pages/financial.subscribes') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $subscribes->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
