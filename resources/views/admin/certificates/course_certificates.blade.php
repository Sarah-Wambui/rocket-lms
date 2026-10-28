{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\WebinarCertificateController@index.
     Certificate PDFs are issued in-house (CvhCertificatesController); the "Verify" link opens the public check for the
     cvh_certificates code when one has been issued for that learner + course. --}}
@extends('admin.layouts.app')

@php
    $cvhCodes = \Illuminate\Support\Facades\DB::table('cvh_certificates')
        ->whereIn('user_id', $certificates->pluck('student_id')->filter()->all())
        ->where('quiz_id', 0)
        ->get()
        ->keyBy(fn($row) => $row->user_id . '-' . $row->webinar_id);
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.competition_certificates') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.competition_certificates') }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.class') }}</label>
                                    <select name="webinars_ids[]" multiple="multiple" class="form-control search-webinar-select2" data-placeholder="{{ trans('admin/main.search_webinar') }}">
                                        @if(!empty($webinars) and $webinars->count() > 0)
                                            @foreach($webinars as $webinar)
                                                <option value="{{ $webinar->id }}" selected>{{ $webinar->title }}</option>
                                            @endforeach
                                        @endif
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
                <div class="card-header">
                    <div class="text-muted font-14">{{ $certificates->total() }} {{ trans('update.competition_certificates') }}</div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th>{{ trans('admin/main.certificate_id') }}</th>
                                <th class="text-left">{{ trans('admin/main.student') }}</th>
                                <th class="text-left">{{ trans('admin/main.course') }}</th>
                                <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                                <th>{{ trans('admin/main.date') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($certificates as $certificate)
                                @php
                                    $item = $certificate->webinar ?? $certificate->bundle;
                                    $cvhCertificate = $cvhCodes[$certificate->student_id . '-' . $certificate->webinar_id] ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $cvhCertificate->code ?? $certificate->id }}</td>
                                    <td class="text-left">
                                        @if(!empty($certificate->student))
                                            <div class="font-weight-bold">{{ $certificate->student->full_name ?: '-' }}</div>
                                            <div class="text-primary text-small">{{ $certificate->student->email }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if(!empty($item))
                                            <a href="{{ $item->getUrl() }}" target="_blank">{{ $item->title }}</a>
                                        @else
                                            {{ trans('update.deleted_item') }}
                                        @endif
                                    </td>
                                    <td class="text-left">{{ (!empty($item) and !empty($item->teacher)) ? $item->teacher->full_name : '-' }}</td>
                                    <td>{{ dateTimeFormat($certificate->created_at, 'j M Y') }}</td>
                                    <td>
                                        @if(!empty($cvhCertificate))
                                            <a href="/certificates/verify?code={{ urlencode($cvhCertificate->code) }}" target="_blank" class="btn-transparent btn-sm text-primary" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.show') }}">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted text-small" data-toggle="tooltip" data-placement="top" title="The PDF is issued when the learner downloads it from My Certificates.">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-30">{{ trans('update.competition_certificates') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $certificates->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
