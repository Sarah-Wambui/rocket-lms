{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\EnrollmentController@history
     (course enrolments = sales with a webinar_id; block / enable access per enrolment). --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.enrollment') }} {{ trans('public.history') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.enrollment') }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.search') }}</label>
                                    <input type="text" class="form-control" name="item_title" value="{{ request()->get('item_title') }}" placeholder="{{ trans('admin/main.course') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.from') }}</label>
                                    <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.to') }}</label>
                                    <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.status') }}</label>
                                    <select name="status" class="form-control">
                                        <option value="">{{ trans('admin/main.all_status') }}</option>
                                        <option value="success" @if(request()->get('status') == 'success') selected @endif>{{ trans('admin/main.success') }}</option>
                                        <option value="refund" @if(request()->get('status') == 'refund') selected @endif>{{ trans('admin/main.refund') }}</option>
                                        <option value="blocked" @if(request()->get('status') == 'blocked') selected @endif>{{ trans('update.access_blocked') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.student') }}</label>
                                    <select name="student_ids[]" multiple="multiple" class="form-control search-user-select2" data-placeholder="{{ trans('admin/main.search_student') }}">
                                        @if(!empty($students) and $students->count() > 0)
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}" selected>{{ $student->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.instructor') }}</label>
                                    <select name="teacher_ids[]" multiple="multiple" class="form-control search-user-select2" data-placeholder="{{ trans('admin/main.search_instructor') }}">
                                        @if(!empty($teachers) and $teachers->count() > 0)
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" selected>{{ $teacher->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.class') }}</label>
                                    <select name="webinar_ids[]" multiple="multiple" class="form-control search-webinar-select2" data-placeholder="{{ trans('admin/main.search_webinar') }}">
                                        @if(!empty($webinars) and $webinars->count() > 0)
                                            @foreach($webinars as $webinar)
                                                <option value="{{ $webinar->id }}" selected>{{ $webinar->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

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
                    <div class="text-muted font-14">{{ $sales->total() }} {{ trans('update.enrollment') }}</div>
                    <div>
                        @can('admin_sales_export')
                            <a href="{{ getAdminPanelUrl('/enrollments/export?' . http_build_query(request()->all())) }}" class="btn btn-outline-primary">{{ trans('admin/main.export_xls') }}</a>
                        @endcan
                        @can('admin_enrollment_add_student_to_items')
                            <a href="{{ getAdminPanelUrl('/enrollments/add-student-to-class') }}" class="btn btn-primary ml-2">{{ trans('update.add_student_to_a_class') }}</a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th>#</th>
                                <th class="text-left">{{ trans('admin/main.student') }}</th>
                                <th class="text-left">{{ trans('admin/main.course') }}</th>
                                <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                                <th>{{ trans('admin/main.type') }}</th>
                                <th>{{ trans('admin/main.date') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($sales as $sale)
                                <tr>
                                    <td>{{ $sale->id }}</td>
                                    <td class="text-left">
                                        @if(!empty($sale->buyer))
                                            <div class="font-weight-bold">{{ $sale->buyer->full_name ?: '-' }}</div>
                                            <div class="text-gray-500 text-small">{{ $sale->buyer->email }}</div>
                                        @else
                                            {{ trans('update.deleted_item') }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        <div>{{ $sale->item_title }}</div>
                                        <div class="text-gray-500 text-small">ID : {{ $sale->item_id }}</div>
                                    </td>
                                    <td class="text-left">{{ $sale->item_seller }}</td>
                                    <td>
                                        @if($sale->manual_added)
                                            {{ trans('public.manual') }}
                                        @elseif(empty($sale->total_amount))
                                            {{ trans('public.free') }}
                                        @else
                                            {{ handlePrice($sale->total_amount) }}
                                        @endif
                                    </td>
                                    <td>{{ dateTimeFormat($sale->created_at, 'j M Y H:i') }}</td>
                                    <td>
                                        @if(!empty($sale->refund_at))
                                            <span class="badge-status text-warning bg-warning-30">{{ trans('admin/main.refund') }}</span>
                                        @elseif(!$sale->access_to_purchased_item)
                                            <span class="badge-status text-danger bg-danger-30">{{ trans('update.access_blocked') }}</span>
                                        @else
                                            <span class="badge-status text-success bg-success-30">{{ trans('admin/main.success') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if(empty($sale->refund_at))
                                            @if($sale->access_to_purchased_item)
                                                @can('admin_enrollment_block_access')
                                                    {{-- The controller deletes a manual enrolment and flags any other one as blocked --}}
                                                    @include('admin.includes.delete_button',[
                                                        'url' => getAdminPanelUrl("/enrollments/{$sale->id}/block-access"),
                                                        'btnClass' => 'btn-sm text-danger',
                                                        'btnText' => $sale->manual_added ? trans('admin/main.remove') : trans('update.block_access'),
                                                        'tooltip' => $sale->manual_added ? trans('admin/main.remove') : trans('update.block_access'),
                                                    ])
                                                @endcan
                                            @else
                                                @can('admin_enrollment_enable_access')
                                                    @include('admin.includes.delete_button',[
                                                        'url' => getAdminPanelUrl("/enrollments/{$sale->id}/enable-access"),
                                                        'btnClass' => 'btn-sm text-success',
                                                        'btnText' => trans('admin/main.active'),
                                                        'tooltip' => trans('admin/main.active'),
                                                        'deleteConfirmMsg' => trans('admin/main.active') . '?',
                                                    ])
                                                @endcan
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-30">{{ trans('update.enrollment') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $sales->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
