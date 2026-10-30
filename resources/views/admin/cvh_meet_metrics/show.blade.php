{{-- CVH Africa: one live class's Google Meet attendance --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $session->title }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/cvh-meet-metrics') }}">Live Class Metrics</a></div>
                <div class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($session->title, 40) }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('cvh_meet_metrics_status'))
                <div class="alert alert-success">{{ session('cvh_meet_metrics_status') }}</div>
            @endif
            @if(session('cvh_meet_metrics_error'))
                <div class="alert alert-danger">{{ session('cvh_meet_metrics_error') }}</div>
            @endif

            <p class="font-14 text-gray-500">
                {{ $session->webinar->title ?? '' }} · scheduled {{ dateTimeFormat($session->date, 'j M Y H:i', false) }} for {{ $session->duration }} min
                @if($row) · last updated from Google {{ dateTimeFormat($row['synced_at'], 'j M Y H:i', false) }}@endif
            </p>

            @if(!$row)
                <div class="alert alert-info">Google has no attendance for this class yet.</div>
            @else
                <div class="row">
                    @foreach([
                        ['Learners attended', $row['learners'] . ' / ' . $row['enrolled'], $row['attendance_rate'] === null ? 'no learners enrolled' : $row['attendance_rate'] . '% of enrolled learners'],
                        ['Guests', $row['guests'], 'not matched to an enrolled learner'],
                        ['Class length', $row['length_minutes'] . ' min', 'scheduled ' . $row['scheduled_minutes'] . ' min'],
                        ['Avg time in class', $row['avg_minutes'] . ' min', $row['avg_percent'] . '% of the class'],
                        ['Joined on time', $row['on_time_percent'] . '%', 'stayed to the end: ' . $row['stayed_percent'] . '%'],
                        ['Rejoins', $row['rejoins'], 'times people dropped and came back'],
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
            @endif

            <div class="card">
                <div class="card-header justify-content-between">
                    <h5 class="font-14 mb-0">Who attended</h5>
                    <div class="d-flex gap-8">
                        @if($row and $row['recording_url'])<a href="{{ $row['recording_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Recording</a>@endif
                        @if($row and $row['transcript_url'])<a href="{{ $row['transcript_url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Transcript</a>@endif
                        <form action="{{ getAdminPanelUrl("/cvh-meet-metrics/{$session->id}/sync") }}" method="post">@csrf
                            <button class="btn btn-sm btn-primary">Update from Google</button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th>Name in Meet</th>
                                <th>LMS user</th>
                                <th>First joined</th>
                                <th>Last left</th>
                                <th class="text-center">Time in class</th>
                                <th class="text-center">Joins</th>
                            </tr>
                            @forelse($attendance as $person)
                                @php
                                    $length = $row ? max(1, $row['length_minutes'] * 60) : 1;
                                    $late = $person->first_join ? (int)round(($person->first_join - (int)$session->date) / 60) : null;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $person->display_name ?: 'Unknown' }}
                                        @if($person->kind === 'anonymous')<span class="badge badge-light">not signed in</span>@endif
                                        @if($person->kind === 'phone')<span class="badge badge-light">phone</span>@endif
                                    </td>
                                    <td>
                                        @if($person->user_id and isset($users[$person->user_id]))
                                            {{ $users[$person->user_id]->full_name }}
                                            @if(in_array($person->user_id, $teacherIds))<span class="badge badge-info">Instructor</span>@endif
                                        @else
                                            <span class="text-gray-500">Guest</span>
                                        @endif
                                    </td>
                                    <td class="font-12">
                                        {{ $person->first_join ? dateTimeFormat($person->first_join, 'H:i', false) : '—' }}
                                        @if($late !== null and $late > 5)<span class="text-warning"> ({{ $late }} min late)</span>@endif
                                    </td>
                                    <td class="font-12">{{ $person->last_leave ? dateTimeFormat($person->last_leave, 'H:i', false) : 'still in the call' }}</td>
                                    <td class="text-center">{{ (int)round($person->seconds / 60) }} min<div class="font-12 text-gray-500">{{ min(100, (int)round(100 * $person->seconds / $length)) }}%</div></td>
                                    <td class="text-center">{{ $person->joins }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-gray-500 py-24">Nobody yet.</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="font-14 mb-0">Enrolled but did not attend ({{ $absent->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($absent->isEmpty())
                        <p class="text-gray-500 mb-0">Every enrolled learner was matched to someone in the meeting.</p>
                    @else
                        <p class="font-12 text-gray-500">Someone here may have joined under a different name in Meet; they would then show as a guest above.</p>
                        <div class="table-responsive">
                            <table class="table custom-table font-14">
                                <tr><th>Name</th><th>Email</th></tr>
                                @foreach($absent as $user)
                                    <tr><td>{{ $user->full_name }}</td><td class="font-12">{{ $user->email }}</td></tr>
                                @endforeach
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
