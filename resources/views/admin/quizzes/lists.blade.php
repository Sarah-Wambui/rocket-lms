{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\QuizController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('admin/pages/quiz.admin_quizzes_list') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('admin/main.quizzes') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                @foreach([
                    ['admin/main.total_quizzes', $totalQuizzes, 'primary'],
                    ['admin/main.active_quizzes', $totalActiveQuizzes, 'success'],
                    ['admin/main.total_students', $totalStudents, 'warning'],
                    ['admin/main.total_passed_students', $totalPassedStudents, 'info'],
                ] as [$label, $count, $color])
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-{{ $color }}">
                                <i class="fas fa-file"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header"><h4>{{ trans($label) }}</h4></div>
                                <div class="card-body">{{ $count }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.search') }}</label>
                                    <input type="text" class="form-control" name="title" value="{{ request()->get('title') }}" placeholder="{{ trans('admin/main.quiz_title') }}">
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
                                    <label class="input-label">{{ trans('admin/main.sort') }}</label>
                                    <select name="sort" class="form-control">
                                        <option value="">{{ trans('admin/main.all') }}</option>
                                        <option value="have_certificate" @if(request()->get('sort') == 'have_certificate') selected @endif>{{ trans('admin/main.quizzes_have_certificate') }}</option>
                                        <option value="students_count_desc" @if(request()->get('sort') == 'students_count_desc') selected @endif>{{ trans('admin/main.students_count') }} ↓</option>
                                        <option value="students_count_asc" @if(request()->get('sort') == 'students_count_asc') selected @endif>{{ trans('admin/main.students_count') }} ↑</option>
                                        <option value="passed_count_desc" @if(request()->get('sort') == 'passed_count_desc') selected @endif>{{ trans('admin/main.passed') }} ↓</option>
                                        <option value="passed_count_asc" @if(request()->get('sort') == 'passed_count_asc') selected @endif>{{ trans('admin/main.passed') }} ↑</option>
                                        <option value="grade_avg_desc" @if(request()->get('sort') == 'grade_avg_desc') selected @endif>{{ trans('admin/main.average_grade') }} ↓</option>
                                        <option value="grade_avg_asc" @if(request()->get('sort') == 'grade_avg_asc') selected @endif>{{ trans('admin/main.average_grade') }} ↑</option>
                                        <option value="created_at_desc" @if(request()->get('sort') == 'created_at_desc') selected @endif>{{ trans('admin/main.create_date_descending') }}</option>
                                        <option value="created_at_asc" @if(request()->get('sort') == 'created_at_asc') selected @endif>{{ trans('admin/main.create_date_ascending') }}</option>
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

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.status') }}</label>
                                    <select name="status" class="form-control">
                                        <option value="">{{ trans('admin/main.all_status') }}</option>
                                        <option value="active" @if(request()->get('status') == 'active') selected @endif>{{ trans('admin/main.active') }}</option>
                                        <option value="inactive" @if(request()->get('status') == 'inactive') selected @endif>{{ trans('admin/main.inactive') }}</option>
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
                    <div class="text-muted font-14">{{ $quizzes->total() }} {{ trans('admin/main.quizzes') }}</div>
                    <div>
                        @can('admin_quizzes_lists_excel')
                            <a href="{{ getAdminPanelUrl('/quizzes/excel?' . http_build_query(request()->all())) }}" class="btn btn-outline-primary">{{ trans('admin/main.export_xls') }}</a>
                        @endcan
                        @can('admin_quizzes_create')
                            <a href="{{ getAdminPanelUrl('/quizzes/create') }}" class="btn btn-primary ml-2">{{ trans('quiz.new_quiz') }}</a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                                <th>{{ trans('admin/main.question_count') }}</th>
                                <th>{{ trans('admin/main.students_count') }}</th>
                                <th>{{ trans('admin/main.average_grade') }}</th>
                                <th>{{ trans('admin/main.passed') }}</th>
                                <th>{{ trans('public.pass_mark') }}</th>
                                <th>{{ trans('admin/main.certificate') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($quizzes as $quiz)
                                @php
                                    $results = $quiz->quizResults;
                                    $studentsCount = $results->pluck('user_id')->unique()->count();
                                    $passedCount = $results->where('status', 'passed')->pluck('user_id')->unique()->count();
                                    $gradeAvg = $results->count() ? round($results->avg('user_grade'), 1) : 0;
                                @endphp
                                <tr>
                                    <td class="text-left">
                                        <div class="font-weight-bold">{{ $quiz->title }}</div>
                                        @if(!empty($quiz->webinar))
                                            <a href="{{ $quiz->webinar->getUrl() }}" target="_blank" class="text-primary text-small">{{ $quiz->webinar->title }}</a>
                                        @endif
                                    </td>
                                    <td class="text-left">{{ !empty($quiz->teacher) ? $quiz->teacher->full_name : '-' }}</td>
                                    <td>{{ $quiz->quizQuestions->count() }}</td>
                                    <td>{{ $studentsCount }}</td>
                                    <td>{{ $gradeAvg }}</td>
                                    <td>{{ $passedCount }}</td>
                                    <td>{{ $quiz->pass_mark }}</td>
                                    <td>
                                        @if($quiz->certificate)
                                            <i class="fa fa-check text-success"></i>
                                        @else
                                            <i class="fa fa-times text-muted"></i>
                                        @endif
                                    </td>
                                    <td>
                                        @if($quiz->status == 'active')
                                            <span class="text-success">{{ trans('admin/main.active') }}</span>
                                        @else
                                            <span class="text-warning">{{ trans('admin/main.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @can('admin_quizzes_results')
                                            <a href="{{ getAdminPanelUrl("/quizzes/{$quiz->id}/results") }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('public.results') }}">
                                                <i class="fa fa-poll"></i>
                                            </a>
                                        @endcan

                                        @can('admin_quizzes_edit')
                                            <a href="{{ getAdminPanelUrl("/quizzes/{$quiz->id}/edit") }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('admin_quizzes_delete')
                                            @include('admin.includes.delete_button',['url' => getAdminPanelUrl("/quizzes/{$quiz->id}/delete"), 'btnClass' => 'btn-sm'])
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-30">{{ trans('admin/main.quizzes') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $quizzes->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
