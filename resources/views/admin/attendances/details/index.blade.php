{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\AttendanceDetailsController@index.
     Top stats, filters and rows (with the mark present / late / absent actions) are the template's own includes in this folder. --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.attendances_history') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/attendances') }}">{{ trans('update.attendances_history') }}</a></div>
                <div class="breadcrumb-item">{{ $session->title }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h2 class="font-16 font-weight-bold text-dark mb-0">{{ $session->title }}</h2>
                    <div class="font-12 text-gray-500 mt-4">
                        @if(!empty($session->webinar))
                            <a href="{{ $session->webinar->getUrl() }}" target="_blank" class="text-gray-500">{{ $session->webinar->title }}</a> |
                        @endif
                        {{ dateTimeFormat($session->date, 'j M Y H:i') }}
                        @if(!empty($session->creator))
                            | {{ $session->creator->full_name }}
                        @endif
                        | {{ trans("update.session_api_{$session->session_api}") }}
                    </div>
                </div>

                <a href="{{ getAdminPanelUrl("/attendances/{$session->id}/details/excel") }}?{{ http_build_query(request()->all()) }}" class="btn btn-primary mt-2 mt-md-0">{{ trans('admin/main.export_xls') }}</a>
            </div>
        </div>

        {{-- Top Stats --}}
        @include('admin.attendances.details.top_stats')

        <div class="section-body">

            {{-- Filters --}}
            @include('admin.attendances.details.filters')

            <section class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.student') }}</th>
                                <th>{{ trans('update.joined_date') }}</th>
                                <th>{{ trans('update.attendance_status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($students as $student)
                                @include('admin.attendances.details.table_items', ['student' => $student, 'session' => $session])
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500 py-30">{{ trans('update.no_data') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $students->appends(request()->input())->links() }}
                </div>
            </section>
        </div>
    </section>
@endsection
