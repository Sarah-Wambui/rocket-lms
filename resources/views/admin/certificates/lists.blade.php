{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\CertificateController@index (quiz certificates).
     Course completion certificates are listed under Certificates > Completion Certificates (course-competition). --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('admin/main.certificate_list_page_title') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.quiz_certificates') }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.quiz_title') }}</label>
                                    <input type="text" class="form-control" name="quiz_title" value="{{ request()->get('quiz_title') }}">
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
                <div class="card-header justify-content-between">
                    <div class="text-muted font-14">{{ $certificates->total() }} {{ trans('update.quiz_certificates') }}</div>
                    @can('admin_certificate_export_excel')
                        <a href="{{ getAdminPanelUrl('/certificates/excel?' . http_build_query(request()->all())) }}" class="btn btn-outline-primary">{{ trans('admin/main.export_xls') }}</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th>{{ trans('admin/main.certificate_id') }}</th>
                                <th class="text-left">{{ trans('admin/main.student') }}</th>
                                <th class="text-left">{{ trans('admin/main.quiz_title') }}</th>
                                <th class="text-left">{{ trans('admin/main.course') }}</th>
                                <th>{{ trans('admin/main.grade') }}</th>
                                <th>{{ trans('admin/main.date') }}</th>
                            </tr>

                            @forelse($certificates as $certificate)
                                <tr>
                                    <td>{{ $certificate->id }}</td>
                                    <td class="text-left">{{ !empty($certificate->student) ? $certificate->student->full_name : '-' }}</td>
                                    <td class="text-left">{{ !empty($certificate->quiz) ? $certificate->quiz->title : '-' }}</td>
                                    <td class="text-left">{{ (!empty($certificate->quiz) and !empty($certificate->quiz->webinar)) ? $certificate->quiz->webinar->title : '-' }}</td>
                                    <td>{{ $certificate->user_grade ?? (!empty($certificate->quizzesResult) ? $certificate->quizzesResult->user_grade : '-') }}</td>
                                    <td>{{ dateTimeFormat($certificate->created_at, 'j M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-30">{{ trans('update.quiz_certificates') }}: 0</td>
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
