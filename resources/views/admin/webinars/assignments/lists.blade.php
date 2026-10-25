{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\AssignmentController@index.
     Each row links to the template's own students page (/assignments/{id}/students) and from there to the conversations. --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.assignments') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.assignments') }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card-statistic">
                    <div class="card-statistic__mask"></div>
                    <div class="card-statistic__wrap">
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="text-gray-500 mt-8">{{ trans('update.course_assignments') }}</span>
                            <div class="d-flex-center size-48 bg-primary-30 rounded-12">
                                <x-iconsax-bul-document-text class="icons text-primary" width="24px" height="24px"/>
                            </div>
                        </div>
                        <h5 class="font-24 mt-12 line-height-1 text-black">{{ $courseAssignmentsCount }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card-statistic">
                    <div class="card-statistic__mask"></div>
                    <div class="card-statistic__wrap">
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="text-gray-500 mt-8">{{ trans('update.pending_review') }}</span>
                            <div class="d-flex-center size-48 bg-warning-30 rounded-12">
                                <x-iconsax-bul-timer class="icons text-warning" width="24px" height="24px"/>
                            </div>
                        </div>
                        <h5 class="font-24 mt-12 line-height-1 text-black">{{ $pendingReviewCount }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card-statistic">
                    <div class="card-statistic__mask"></div>
                    <div class="card-statistic__wrap">
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="text-gray-500 mt-8">{{ trans('quiz.passed') }}</span>
                            <div class="d-flex-center size-48 bg-success-30 rounded-12">
                                <x-iconsax-bul-user-tick class="icons text-success" width="24px" height="24px"/>
                            </div>
                        </div>
                        <h5 class="font-24 mt-12 line-height-1 text-black">{{ $passedCount }}</h5>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card-statistic">
                    <div class="card-statistic__mask"></div>
                    <div class="card-statistic__wrap">
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="text-gray-500 mt-8">{{ trans('quiz.failed') }}</span>
                            <div class="d-flex-center size-48 bg-danger-30 rounded-12">
                                <x-iconsax-bul-user-remove class="icons text-danger" width="24px" height="24px"/>
                            </div>
                        </div>
                        <h5 class="font-24 mt-12 line-height-1 text-black">{{ $failedCount }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-body">
            <section class="card mt-32">
                <div class="card-body pb-4">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.start_date') }}</label>
                                    <input type="date" class="text-center form-control" name="from" value="{{ request()->get('from') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.end_date') }}</label>
                                    <input type="date" class="text-center form-control" name="to" value="{{ request()->get('to') }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.class') }}</label>
                                    <select name="webinar_ids[]" multiple="multiple" class="form-control search-webinar-select2" data-placeholder="Search classes">
                                        @if(!empty($webinars) and $webinars->count() > 0)
                                            @foreach($webinars as $webinar)
                                                <option value="{{ $webinar->id }}" selected>{{ $webinar->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.status') }}</label>
                                    <select name="status" class="form-control">
                                        <option value="">{{ trans('public.all') }}</option>
                                        <option value="active" {{ request()->get('status') == 'active' ? 'selected' : '' }}>{{ trans('admin/main.active') }}</option>
                                        <option value="inactive" {{ request()->get('status') == 'inactive' ? 'selected' : '' }}>{{ trans('admin/main.inactive') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-center">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">{{ trans('admin/main.show_results') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                <th class="text-left">{{ trans('admin/main.class') }}</th>
                                <th>{{ trans('admin/main.students') }}</th>
                                <th>{{ trans('update.min_grade') }}</th>
                                <th>{{ trans('quiz.average_grade') }}</th>
                                <th>{{ trans('update.submissions') }}</th>
                                <th>{{ trans('public.pending') }}</th>
                                <th>{{ trans('update.deadline') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($assignments as $assignment)
                                @php
                                    $histories = $assignment->instructorAssignmentHistories;
                                    $submittedHistories = $histories->where('status', '!=', \App\Models\WebinarAssignmentHistory::$notSubmitted);
                                    $gradedHistories = $histories->whereNotNull('grade');
                                @endphp
                                <tr class="text-center">
                                    <td class="text-left">
                                        <span class="d-block text-dark font-weight-500">{{ $assignment->title }}</span>
                                        @if(!empty($assignment->chapter))
                                            <span class="d-block font-12 text-gray-500 mt-4">{{ $assignment->chapter->title }}</span>
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if(!empty($assignment->webinar))
                                            <a href="{{ $assignment->webinar->getUrl() }}" target="_blank" class="text-dark">{{ $assignment->webinar->title }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $histories->count() }}</td>
                                    <td>{{ $assignment->pass_grade ?? '-' }}{{ !empty($assignment->grade) ? '/' . $assignment->grade : '' }}</td>
                                    <td>{{ $gradedHistories->count() ? round($gradedHistories->avg('grade'), 2) : '-' }}</td>
                                    <td>{{ $submittedHistories->sum(fn($history) => $history->messages->count()) }}</td>
                                    <td>{{ $histories->where('status', \App\Models\WebinarAssignmentHistory::$pending)->count() }}</td>
                                    <td>{{ !empty($assignment->deadline) ? $assignment->deadline . ' ' . trans('public.days') : '-' }}</td>
                                    <td>
                                        @if($assignment->status == 'active')
                                            <span class="badge-status text-success bg-success-30">{{ trans('admin/main.active') }}</span>
                                        @else
                                            <span class="badge-status text-warning bg-warning-30">{{ trans('admin/main.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group dropdown table-actions position-relative">
                                            <button type="button" class="btn-transparent dropdown-toggle" data-toggle="dropdown">
                                                <x-iconsax-lin-more class="icons text-gray-500" width="20px" height="20px"/>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-right">
                                                @can('admin_webinar_assignments_students')
                                                    <a href="{{ getAdminPanelUrl("/assignments/{$assignment->id}/students") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-profile-2user class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 font-14">{{ trans('admin/main.students') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_webinars_edit')
                                                    @if(!empty($assignment->webinar))
                                                        <a href="{{ getAdminPanelUrl("/webinars/{$assignment->webinar_id}/edit") }}" target="_blank" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                            <x-iconsax-lin-edit-2 class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                            <span class="text-gray-500 font-14">{{ trans('admin/main.edit') }}</span>
                                                        </a>
                                                    @endif

                                                    @include('admin.includes.delete_button', [
                                                        'url' => getAdminPanelUrl("/assignments/{$assignment->id}/delete"),
                                                        'btnClass' => 'dropdown-item text-danger mb-0 py-3 px-0 font-14 d-flex align-items-center gap-4',
                                                        'btnText' => trans('admin/main.delete'),
                                                        'btnIcon' => 'trash',
                                                        'iconType' => 'lin',
                                                        'iconClass' => 'text-danger mr-2',
                                                    ])
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-gray-500 py-30">{{ trans('update.no_data') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $assignments->appends(request()->input())->links() }}
                </div>
            </section>
        </div>
    </section>
@endsection
