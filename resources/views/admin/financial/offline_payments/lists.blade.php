{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\OfflinePaymentController@index
     (page_type=requests: waiting requests with approve/reject; page_type=history: approved and rejected ones) --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('admin/main.offline_payments') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('admin/main.offline_payments') }}</div>
            </div>
        </div>

        <div class="section-body">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $pageType == 'requests' ? 'active' : '' }}" href="{{ getAdminPanelUrl('/financial/offline_payments?page_type=requests') }}">{{ trans('admin/main.waiting') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $pageType == 'history' ? 'active' : '' }}" href="{{ getAdminPanelUrl('/financial/offline_payments?page_type=history') }}">{{ trans('update.history') }}</a>
                </li>
            </ul>

            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <input type="hidden" name="page_type" value="{{ $pageType }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.search') }}</label>
                                    <input type="text" class="form-control" name="search" value="{{ request()->get('search') }}" placeholder="{{ trans('admin/main.user') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.from') }}</label>
                                    <div class="input-group">
                                        <input type="date" id="from" class="text-center form-control" name="from" value="{{ request()->get('from') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.to') }}</label>
                                    <div class="input-group">
                                        <input type="date" id="to" class="text-center form-control" name="to" value="{{ request()->get('to') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.sort') }}</label>
                                    <select name="sort" class="form-control">
                                        <option value="">{{ trans('update.default') }}</option>
                                        <option value="amount_asc" @if(request()->get('sort') == 'amount_asc') selected @endif>{{ trans('update.amount_asc') }}</option>
                                        <option value="amount_desc" @if(request()->get('sort') == 'amount_desc') selected @endif>{{ trans('update.amount_desc') }}</option>
                                        <option value="pay_date_asc" @if(request()->get('sort') == 'pay_date_asc') selected @endif>{{ trans('admin/main.transaction_time') }} ↑</option>
                                        <option value="pay_date_desc" @if(request()->get('sort') == 'pay_date_desc') selected @endif>{{ trans('admin/main.transaction_time') }} ↓</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.users') }}</label>
                                    <select name="user_ids[]" multiple="multiple" class="form-control search-user-select2" data-placeholder="{{ trans('public.search_user') }}">
                                        @if(!empty($users) and $users->count() > 0)
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" selected>{{ $user->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.role') }}</label>
                                    <select name="role_id" class="form-control">
                                        <option value="">{{ trans('admin/main.all_roles') }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" @if(request()->get('role_id') == $role->id) selected @endif>{{ $role->caption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('financial.account_type') }}</label>
                                    <select name="account_type" class="form-control">
                                        <option value="">{{ trans('admin/main.all') }}</option>
                                        @foreach($offlineBanks as $offlineBank)
                                            <option value="{{ $offlineBank->id }}" @if(request()->get('account_type') == $offlineBank->id) selected @endif>{{ $offlineBank->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if($pageType == 'history')
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.status') }}</label>
                                        <select name="status" class="form-control">
                                            <option value="">{{ trans('admin/main.all') }}</option>
                                            <option value="approved" @if(request()->get('status') == 'approved') selected @endif>{{ trans('admin/main.approved') }}</option>
                                            <option value="reject" @if(request()->get('status') == 'reject') selected @endif>{{ trans('admin/main.rejected') }}</option>
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary w-100">{{ trans('admin/main.show_results') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <div class="card">
                <div class="card-header justify-content-between">
                    <div class="text-muted font-14">{{ $offlinePayments->total() }} {{ trans('admin/main.offline_payments') }}</div>
                    @can('admin_offline_payments_export_excel')
                        <a href="{{ getAdminPanelUrl('/financial/offline_payments/excel') . '?' . http_build_query(request()->all()) }}" class="btn btn-primary">{{ trans('admin/main.export_xls') }}</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.user') }}</th>
                                <th>{{ trans('admin/main.amount') }}</th>
                                <th>{{ trans('financial.bank') }}</th>
                                <th>{{ trans('admin/main.referral_code') }}</th>
                                <th>{{ trans('admin/main.transaction_time') }}</th>
                                <th>{{ trans('update.attachment') }}</th>
                                <th>{{ trans('admin/main.type') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($offlinePayments as $offlinePayment)
                                <tr>
                                    <td class="text-left">
                                        @if(!empty($offlinePayment->user))
                                            <div class="d-flex align-items-center">
                                                <figure class="avatar mr-2">
                                                    <img src="{{ $offlinePayment->user->getAvatar() }}" alt="{{ $offlinePayment->user->full_name }}">
                                                </figure>
                                                <div class="media-body ml-1">
                                                    <div class="mt-0 mb-1 font-weight-bold">{{ $offlinePayment->user->full_name }}</div>
                                                    <div class="text-primary text-small font-600-bold">{{ $offlinePayment->user->email ?? $offlinePayment->user->mobile }}</div>
                                                    <div class="text-muted text-small">{{ $offlinePayment->user->role->caption ?? $offlinePayment->user->role_name }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ handlePrice($offlinePayment->amount) }}</td>
                                    <td>{{ !empty($offlinePayment->offlineBank) ? $offlinePayment->offlineBank->title : '-' }}</td>
                                    <td>{{ $offlinePayment->reference_number }}</td>
                                    <td>{{ is_numeric($offlinePayment->pay_date) ? dateTimeFormat($offlinePayment->pay_date, 'j M Y H:i') : $offlinePayment->pay_date }}</td>
                                    <td>
                                        @if(!empty($offlinePayment->getAttachmentPath()))
                                            <a href="{{ $offlinePayment->getAttachmentPath() }}" target="_blank" class="text-primary">{{ trans('admin/main.show') }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($offlinePayment->type == \App\Models\OfflinePayment::$typeCart and !empty($offlinePayment->order_id))
                                            <a href="{{ getAdminPanelUrl("/financial/offline_payments/{$offlinePayment->id}/cart-items") }}" class="text-primary">{{ trans('update.cart') }}</a>
                                        @else
                                            {{ trans('update.wallet_charge') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($offlinePayment->status == \App\Models\OfflinePayment::$waiting)
                                            <span class="text-warning">{{ trans('admin/main.waiting') }}</span>
                                        @elseif($offlinePayment->status == \App\Models\OfflinePayment::$approved)
                                            <span class="text-success">{{ trans('admin/main.approved') }}</span>
                                        @else
                                            <span class="text-danger">{{ trans('admin/main.rejected') }}</span>
                                        @endif
                                        <div class="text-muted text-small">{{ dateTimeFormat($offlinePayment->created_at, 'j M Y H:i') }}</div>
                                    </td>
                                    <td>
                                        {{-- Approve/reject only while waiting: the controller credits the wallet on every approve call --}}
                                        @if($offlinePayment->status == \App\Models\OfflinePayment::$waiting)
                                            @can('admin_offline_payments_approved')
                                                @include('admin.includes.delete_button',[
                                                    'url' => getAdminPanelUrl("/financial/offline_payments/{$offlinePayment->id}/approved"),
                                                    'btnClass' => 'btn btn-sm btn-success text-white mb-1',
                                                    'btnText' => trans('admin/main.approve'),
                                                    'noBtnTransparent' => true,
                                                    'deleteConfirmMsg' => trans('admin/main.approve') . ': ' . handlePrice($offlinePayment->amount) . '?',
                                                ])
                                            @endcan

                                            @can('admin_offline_payments_reject')
                                                @include('admin.includes.delete_button',[
                                                    'url' => getAdminPanelUrl("/financial/offline_payments/{$offlinePayment->id}/reject"),
                                                    'btnClass' => 'btn btn-sm btn-danger text-white mb-1',
                                                    'btnText' => trans('admin/main.reject'),
                                                    'noBtnTransparent' => true,
                                                ])
                                            @endcan
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-30">{{ trans('admin/main.offline_payments') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $offlinePayments->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
