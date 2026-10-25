{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\AttendanceController@index.
     Top stats, filters and rows are the template's own includes in this folder. --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.attendances_history') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.attendances_history') }}</div>
            </div>
        </div>

        {{-- Top Stats --}}
        @include('admin.attendances.history.top_stats')

        <div class="section-body">

            {{-- Filters --}}
            @include('admin.attendances.history.filters')

            <section class="card">
                <div class="card-header justify-content-end">
                    <a href="{{ getAdminPanelUrl('/attendances/excel') }}?{{ http_build_query(request()->all()) }}" class="btn btn-primary">{{ trans('admin/main.export_xls') }}</a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('update.session') }}</th>
                                <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                                <th>{{ trans('admin/main.start_date') }}</th>
                                <th>{{ trans('update.source') }}</th>
                                <th>{{ trans('admin/main.students') }}</th>
                                <th>{{ trans('update.present') }}</th>
                                <th>{{ trans('update.late') }}</th>
                                <th>{{ trans('update.absent') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($sessions as $session)
                                @include('admin.attendances.history.table_items', ['session' => $session])
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-gray-500 py-30">{{ trans('update.no_data') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $sessions->appends(request()->input())->links() }}
                </div>
            </section>
        </div>
    </section>
@endsection
