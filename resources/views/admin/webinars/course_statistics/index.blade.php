{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\WebinarStatisticController@index.
     Charts are drawn by the template's /assets/admin/js/parts/course_statistics.min.js (Chart.js 2): makePieChart(),
     handleMonthlySalesChart() and handleCourseProgressChart(); the pie cards use the template's includes/pie_charts. --}}
@extends('admin.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/default/vendors/chartjs/chart.min.css"/>
@endpush

@php
    $statCards = [
        ['label' => trans('public.students'), 'value' => $studentsCount, 'icon' => 'profile-2user', 'color' => 'primary'],
        ['label' => trans('panel.comments'), 'value' => $commentsCount, 'icon' => 'message-text', 'color' => 'success'],
        ['label' => trans('panel.sales'), 'value' => $salesCount, 'icon' => 'bag-2', 'color' => 'accent'],
        ['label' => trans('financial.total_sales'), 'value' => handlePrice($salesAmount), 'icon' => 'dollar-square', 'color' => 'secondary'],
        ['label' => trans('public.chapters'), 'value' => $chaptersCount, 'icon' => 'book', 'color' => 'primary'],
        ['label' => trans('public.sessions'), 'value' => $sessionsCount, 'icon' => 'video', 'color' => 'danger'],
        ['label' => trans('update.pending_quizzes'), 'value' => $pendingQuizzesCount, 'icon' => 'clipboard-text', 'color' => 'warning'],
        ['label' => trans('update.pending_assignments'), 'value' => $pendingAssignmentsCount, 'icon' => 'document-text', 'color' => 'secondary'],
    ];
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.course_statistics') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/webinars') }}">{{ trans('admin/main.classes') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.course_statistics') }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="size-48 rounded-8 bg-gray-100 mr-12">
                        <img src="{{ $webinar->getIcon() }}" class="img-cover rounded-8" alt="{{ $webinar->title }}">
                    </div>
                    <div>
                        <a href="{{ $webinar->getUrl() }}" target="_blank" class="font-16 font-weight-bold text-dark">{{ $webinar->title }}</a>
                        <div class="font-12 text-gray-500 mt-4">
                            {{ !empty($webinar->teacher) ? $webinar->teacher->full_name : '' }}
                            | {{ dateTimeFormat($webinar->created_at, 'j M Y') }}
                        </div>
                    </div>
                </div>

                @can('admin_webinars_edit')
                    <a href="{{ getAdminPanelUrl("/webinars/{$webinar->id}/edit") }}" class="btn btn-primary mt-2 mt-md-0">{{ trans('admin/main.edit') }}</a>
                @endcan
            </div>
        </div>

        {{-- Top stats --}}
        <div class="row">
            @foreach($statCards as $card)
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card-statistic">
                        <div class="card-statistic__mask"></div>
                        <div class="card-statistic__wrap">
                            <div class="d-flex align-items-start justify-content-between">
                                <span class="text-gray-500 mt-8">{{ $card['label'] }}</span>
                                <div class="d-flex-center size-48 bg-{{ $card['color'] }}-30 rounded-12">
                                    @svg("iconsax-bul-{$card['icon']}", ['width' => '24px', 'height' => '24px', 'class' => "icons text-{$card['color']}"])
                                </div>
                            </div>
                            <h5 class="font-24 mt-12 line-height-1 text-black">{{ $card['value'] }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="section-body">

            {{-- Averages --}}
            <div class="row">
                <div class="col-12 col-md-3 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white h-100">
                        <span class="d-block font-14 text-gray-500">{{ trans('update.average_rating') }}</span>
                        <div class="d-flex align-items-center mt-2">
                            <span class="font-24 font-weight-bold text-dark mr-2">{{ $courseRate ?: 0 }}</span>
                            <div class="stars-card d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <i data-feather="star" width="16" height="16" class="{{ $i <= round($courseRate) ? 'active' : '' }}"></i>
                                @endfor
                            </div>
                        </div>
                        <span class="d-block font-12 text-gray-500 mt-1">{{ $courseRateCount }} {{ trans('update.total_rates') }}</span>
                    </div>
                </div>

                <div class="col-12 col-md-3 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white h-100">
                        <span class="d-block font-14 text-gray-500">{{ trans('quiz.quizzes') }} - {{ trans('quiz.average_grade') }}</span>
                        <span class="d-block font-24 font-weight-bold text-dark mt-2">{{ $quizzesAverageGrade ?: 0 }}</span>
                    </div>
                </div>

                <div class="col-12 col-md-3 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white h-100">
                        <span class="d-block font-14 text-gray-500">{{ trans('update.assignments') }} - {{ trans('quiz.average_grade') }}</span>
                        <span class="d-block font-24 font-weight-bold text-dark mt-2">{{ $assignmentsAverageGrade ?: 0 }}</span>
                    </div>
                </div>

                <div class="col-12 col-md-3 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white h-100">
                        <span class="d-block font-14 text-gray-500">{{ trans('update.forum_messages') }}</span>
                        <span class="d-block font-24 font-weight-bold text-dark mt-2">{{ $courseForumsMessagesCount }}</span>
                        <span class="d-block font-12 text-gray-500 mt-1">{{ $courseForumsStudentsCount }} {{ trans('update.forum_students') }}</span>
                    </div>
                </div>
            </div>

            {{-- Pie charts --}}
            <div class="row">
                @include('admin.webinars.course_statistics.includes.pie_charts', [
                    'cardTitle' => trans('update.students_user_roles'),
                    'cardId' => 'studentsUserRolesChart',
                    'cardPrimaryLabel' => trans('public.students'),
                    'cardSecondaryLabel' => trans('public.instructors'),
                    'cardWarningLabel' => trans('home.organizations'),
                ])

                @include('admin.webinars.course_statistics.includes.pie_charts', [
                    'cardTitle' => trans('update.course_progress'),
                    'cardId' => 'courseProgressChart',
                    'cardPrimaryLabel' => trans('update.completed'),
                    'cardSecondaryLabel' => trans('webinars.in_progress'),
                    'cardWarningLabel' => trans('update.not_started'),
                ])

                @include('admin.webinars.course_statistics.includes.pie_charts', [
                    'cardTitle' => trans('update.quiz_status'),
                    'cardId' => 'quizStatusChart',
                    'cardPrimaryLabel' => trans('quiz.passed'),
                    'cardSecondaryLabel' => trans('public.pending'),
                    'cardWarningLabel' => trans('quiz.failed'),
                ])

                @include('admin.webinars.course_statistics.includes.pie_charts', [
                    'cardTitle' => trans('update.assignments_status'),
                    'cardId' => 'assignmentsStatusChart',
                    'cardPrimaryLabel' => trans('quiz.passed'),
                    'cardSecondaryLabel' => trans('public.pending'),
                    'cardWarningLabel' => trans('quiz.failed'),
                ])
            </div>

            {{-- Line / bar charts --}}
            <div class="row">
                <div class="col-12 col-md-6 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white">
                        <span class="d-block font-16 font-weight-bold text-dark">{{ trans('update.month_sales') }}</span>
                        <div class="mt-3">
                            <canvas id="monthlySalesChart" height="150"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 mt-3">
                    <div class="course-statistic-cards-shadow p-16 rounded-8 bg-white">
                        <span class="d-block font-16 font-weight-bold text-dark">{{ trans('update.students_progress') }} (%)</span>
                        <div class="mt-3">
                            <canvas id="courseProgressLineChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Students --}}
            <section class="card mt-32">
                <div class="card-header">
                    <h4 class="mb-0">{{ trans('update.course_students') }}</h4>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('quiz.student') }}</th>
                                <th>{{ trans('update.progress') }}</th>
                                <th>{{ trans('update.passed_quizzes') }}</th>
                                <th>{{ trans('update.unsent_assignments') }}</th>
                                <th>{{ trans('update.pending_assignments') }}</th>
                                <th>{{ trans('panel.purchase_date') }}</th>
                            </tr>

                            @foreach($students as $user)
                                <tr class="text-center">
                                    <td class="text-left">
                                        <div class="user-inline-avatar d-flex align-items-center">
                                            <div class="avatar bg-gray200">
                                                <img src="{{ $user->getAvatar() }}" class="img-cover" alt="">
                                            </div>
                                            <div class="ml-1">
                                                <span class="d-block font-weight-500">{{ $user->full_name ?: '-' }}</span>
                                                <span class="mt-1 font-12 text-gray-500 d-block">{{ $user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="progress w-50" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $user->course_progress }}%"></div>
                                            </div>
                                            <span class="ml-2 font-12">{{ $user->course_progress }}%</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->passed_quizzes }}</td>
                                    <td>{{ $user->unsent_assignments }}</td>
                                    <td>{{ $user->pending_assignments }}</td>
                                    @php
                                        $userSale = \App\Models\Sale::query()->where('buyer_id', $user->id)->where('webinar_id', $webinar->id)->whereNull('refund_at')->first();
                                    @endphp
                                    <td>{{ !empty($userSale) ? dateTimeFormat($userSale->created_at, 'j M Y') : '-' }}</td>
                                </tr>
                            @endforeach

                            @foreach($unregisteredUsers as $unregisteredUser)
                                <tr class="text-center">
                                    <td class="text-left">
                                        <span class="d-block font-weight-500">{{ $unregisteredUser->full_name }}</span>
                                        <span class="mt-1 font-12 text-gray-500 d-block">{{ $unregisteredUser->email }}</span>
                                    </td>
                                    <td colspan="5" class="text-gray-500">{{ trans('update.unregistered') }}</td>
                                </tr>
                            @endforeach

                            @if($students->isEmpty() and $unregisteredUsers->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500 py-30">{{ trans('update.course_statistic_students_no_result') }}</td>
                                </tr>
                            @endif
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

@push('scripts_bottom')
    <script src="/assets/default/vendors/chartjs/chart.min.js"></script>
    <script src="/assets/default/vendors/feather-icons/dist/feather.min.js"></script>
    <script src="/assets/admin/js/parts/course_statistics.min.js"></script>

    <script>
        (function ($) {
            "use strict";

            makePieChart('studentsUserRolesChart', @json($studentsUserRolesChart['labels']), @json($studentsUserRolesChart['data']));
            makePieChart('courseProgressChart', @json($courseProgressChart['labels']), @json($courseProgressChart['data']));
            makePieChart('quizStatusChart', @json($quizStatusChart['labels']), @json($quizStatusChart['data']));
            makePieChart('assignmentsStatusChart', @json($assignmentsStatusChart['labels']), @json($assignmentsStatusChart['data']));

            handleMonthlySalesChart(@json($monthlySalesChart['labels']), @json($monthlySalesChart['data']));
            handleCourseProgressChart(@json($courseProgressLineChart['labels']), @json($courseProgressLineChart['data']));

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        })(jQuery);
    </script>
@endpush
