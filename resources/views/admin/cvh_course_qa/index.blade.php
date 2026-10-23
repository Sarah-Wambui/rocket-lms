{{-- CVH Africa: admin overview of course Q&A --}}
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
            @if(session('cvh_qa_admin_status'))
                <div class="alert alert-success">{{ session('cvh_qa_admin_status') }}</div>
            @endif

            <div class="row">
                @foreach(['questions' => 'Questions', 'open' => 'Open questions', 'answers' => 'Answers'] as $key => $label)
                    <div class="col-12 col-md-4">
                        <div class="card card-statistic-1"><div class="card-wrap p-16">
                            <div class="font-12 text-gray-500">{{ $label }}</div>
                            <div class="font-24 font-weight-bold">{{ $stats[$key] }}</div>
                        </div></div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">Questions from all courses</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">Learners ask and answer these on each course's Q&A page. Open a question to answer, pin or mark a solution.</p>
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
                        <select name="status" class="form-control" style="max-width: 180px;">
                            <option value="">Any status</option>
                            <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" style="max-width: 280px;" placeholder="Search questions">
                        <button class="btn btn-primary">Filter</button>
                    </form>

                    <table class="table custom-table font-14">
                        <tr>
                            <th>Question</th>
                            <th>Course</th>
                            <th>Asked by</th>
                            <th class="text-center">Answers</th>
                            <th class="text-center">Status</th>
                            <th>Asked on</th>
                            <th class="text-right">Actions</th>
                        </tr>
                        @forelse($questions as $q)
                            <tr>
                                <td class="font-weight-bold">{{ \Illuminate\Support\Str::limit($q->title, 60) }}</td>
                                <td class="font-12">{{ \Illuminate\Support\Str::limit($courses[$q->webinar_id] ?? '—', 40) }}</td>
                                <td>{{ $users[$q->user_id]->full_name ?? 'Former member' }}</td>
                                <td class="text-center">{{ $q->answers_count }}</td>
                                <td class="text-center">
                                    @if($q->resolved_answer_id)<span class="badge badge-success">Resolved</span>@else<span class="badge badge-warning">Open</span>@endif
                                    @if($q->is_pinned)<span class="badge badge-info">Pinned</span>@endif
                                </td>
                                <td class="font-12">{{ dateTimeFormat($q->created_at, 'j M Y', false) }}</td>
                                <td class="text-right">
                                    <div class="d-inline-flex gap-4">
                                        <a href="/course/{{ $q->course_slug }}/questions/{{ $q->id }}" target="_blank" class="btn btn-sm btn-primary">Open</a>
                                        <form action="{{ getAdminPanelUrl("/cvh-course-qa/{$q->id}/delete") }}" method="post" onsubmit="return confirm('Delete this question and its answers?')">@csrf
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-gray-500 py-24">No questions yet.</td></tr>
                        @endforelse
                    </table>

                    @if($questions->hasPages())
                        <div class="mt-16">{{ $questions->links('pagination::bootstrap-4') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
