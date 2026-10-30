{{-- CVH Africa: Google Meet engagement metrics for live classes --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item active">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('cvh_meet_metrics_status'))
                <div class="alert alert-success">{{ session('cvh_meet_metrics_status') }}</div>
            @endif
            @if(session('cvh_meet_metrics_error'))
                <div class="alert alert-danger">{{ session('cvh_meet_metrics_error') }}</div>
            @endif

            @if(!$connected)
                <div class="alert alert-warning">Google Meet is not connected. Connect the Google account in Settings → General → Features → Google Meet; attendance is then fetched every hour.</div>
            @elseif(!$ready)
                <div class="alert alert-warning">Run <code>php database/cvh/26_meet_metrics.php</code> on the server to create the metrics tables.</div>
            @endif

            @php $t = $metrics['totals']; @endphp
            <div class="row">
                @foreach([
                    ['Live classes held', $t['classes'], 'with attendance from Google'],
                    ['Attendance rate', $t['attendance_rate'] === null ? '—' : $t['attendance_rate'] . '%', 'of enrolled learners joined, on average'],
                    ['Participants', $t['attendances'], $t['learners'] . ' of them matched to enrolled learners'],
                    ['Time in class', $t['avg_minutes'] . ' min', $t['avg_percent'] . '% of the class, on average'],
                    ['Joined on time', $t['on_time_percent'] . '%', 'within 5 minutes of the start'],
                    ['Recorded', $t['recorded'] . ' / ' . $t['classes'], 'classes with a recording'],
                ] as [$label, $value, $hint])
                    <div class="col-12 col-md-4 col-xl-2">
                        <div class="card card-statistic-1"><div class="card-wrap p-16">
                            <div class="font-12 text-gray-500">{{ $label }}</div>
                            <div class="font-24 font-weight-bold">{{ $value }}</div>
                            <div class="font-12 text-gray-500 mt-4">{{ $hint }}</div>
                        </div></div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">Google Meet live classes</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">Updated from Google every hour for 30 days after each class. People are matched to learners by their name in Meet; the rest count as guests. Instructors are left out of the averages.</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="get" class="d-flex flex-wrap gap-8 mb-16">
                        <select name="course_id" class="form-control" style="max-width: 320px;">
                            <option value="">All courses</option>
                            @foreach($courses as $id => $title)
                                <option value="{{ $id }}" {{ (string)$courseId === (string)$id ? 'selected' : '' }}>{{ $title }}</option>
                            @endforeach
                        </select>
                        <select name="period" class="form-control" style="max-width: 180px;">
                            @foreach($periods as $value => $label)
                                <option value="{{ $value }}" {{ $period === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary">Filter</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th>Class</th>
                                <th>Date</th>
                                <th class="text-center">Length</th>
                                <th class="text-center">Learners</th>
                                <th class="text-center">Guests</th>
                                <th class="text-center">Avg time</th>
                                <th class="text-center">On time</th>
                                <th class="text-center">Stayed to end</th>
                                <th class="text-center">Recording</th>
                                <th class="text-right">Actions</th>
                            </tr>
                            @forelse($sessions as $session)
                                @php $row = $metrics['rows'][$session->id]; @endphp
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ \Illuminate\Support\Str::limit($session->title, 50) }}</div>
                                        <div class="font-12 text-gray-500">{{ \Illuminate\Support\Str::limit($session->webinar->title ?? '—', 50) }}</div>
                                    </td>
                                    <td class="font-12">{{ dateTimeFormat($session->date, 'j M Y H:i', false) }}</td>
                                    @if($row)
                                        <td class="text-center">{{ $row['length_minutes'] }} min</td>
                                        <td class="text-center">
                                            {{ $row['learners'] }} / {{ $row['enrolled'] }}
                                            @if($row['attendance_rate'] !== null)<div class="font-12 text-gray-500">{{ $row['attendance_rate'] }}%</div>@endif
                                        </td>
                                        <td class="text-center">{{ $row['guests'] }}</td>
                                        <td class="text-center">{{ $row['avg_minutes'] }} min<div class="font-12 text-gray-500">{{ $row['avg_percent'] }}%</div></td>
                                        <td class="text-center">{{ $row['on_time_percent'] }}%</td>
                                        <td class="text-center">{{ $row['stayed_percent'] }}%</td>
                                        <td class="text-center">
                                            @if($row['recording_url'])<a href="{{ $row['recording_url'] }}" target="_blank" rel="noopener">Open</a>@else<span class="text-gray-500">—</span>@endif
                                        </td>
                                    @else
                                        <td colspan="7" class="text-center font-12 text-gray-500">No attendance from Google yet</td>
                                    @endif
                                    <td class="text-right">
                                        <div class="d-inline-flex gap-4">
                                            @if($row)
                                                <a href="{{ getAdminPanelUrl("/cvh-meet-metrics/{$session->id}") }}" class="btn btn-sm btn-primary">Details</a>
                                            @endif
                                            <form action="{{ getAdminPanelUrl("/cvh-meet-metrics/{$session->id}/sync") }}" method="post">@csrf
                                                <button class="btn btn-sm btn-outline-primary" @if(!$ready) disabled @endif>Update</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-gray-500 py-24">No Google Meet live classes in this period.</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
