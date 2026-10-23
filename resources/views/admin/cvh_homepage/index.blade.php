{{-- CVH Homepage Editor: list of homepage sections --}}
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
            @if(session('cvh_homepage_status'))
                <div class="alert alert-success">{{ session('cvh_homepage_status') }}</div>
            @endif

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">Homepage sections</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">Edit, show/hide and reorder the sections of the homepage, top to bottom.</p>
                    </div>
                    <div class="d-flex align-items-center gap-8">
                        <form action="{{ getAdminPanelUrl('/cvh-homepage') }}" method="post" class="d-flex gap-8">
                            @csrf
                            <select name="type" class="form-control form-control-sm" required>
                                <option value="">Add a section…</option>
                                @foreach($addableTypes as $type => $title)
                                    <option value="{{ $type }}">{{ $title }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Add</button>
                        </form>
                        <a href="/" target="_blank" class="btn btn-outline-primary btn-sm">View homepage</a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table custom-table font-14">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Section</th>
                            <th>Shows</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                        @foreach($sections as $section)
                            @php
                                $c = json_decode($section->content ?? '{}', true) ?: [];
                                $summary = $c['main_content']['title'] ?? (isset($c['main_content']['title_line_1']) ? trim(($c['main_content']['title_line_1'] ?? '') . ' … ' . ($c['main_content']['title_line_2'] ?? '')) : null);
                                if ($section->type === 'vertical_spacer') $summary = ($c['space_number'] ?? 0) . ' px of space';
                                if ($section->type === 'statistics') $summary = collect($c['statistics'] ?? [])->pluck('title')->filter()->implode(' · ');
                                $title = $schema[$section->type]['title'] ?? $section->type;
                            @endphp
                            <tr class="{{ $section->enable ? '' : 'text-gray-500' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-weight-bold">{{ $title }}</td>
                                <td class="font-12">{{ \Illuminate\Support\Str::limit($summary ?? '—', 70) }}</td>
                                <td class="text-center">
                                    @if($section->enable)
                                        <span class="badge badge-success">Visible</span>
                                    @else
                                        <span class="badge badge-secondary">Hidden</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="d-inline-flex align-items-center gap-4">
                                        @foreach(['up' => '↑', 'down' => '↓'] as $dir => $arrow)
                                            <form action="{{ getAdminPanelUrl("/cvh-homepage/{$section->id}/move/{$dir}") }}" method="post">@csrf
                                                <button class="btn btn-sm btn-outline-secondary" title="Move {{ $dir }}" {{ (($dir === 'up' and $loop->parent->first) or ($dir === 'down' and $loop->parent->last)) ? 'disabled' : '' }}>{{ $arrow }}</button>
                                            </form>
                                        @endforeach
                                        <form action="{{ getAdminPanelUrl("/cvh-homepage/{$section->id}/toggle") }}" method="post">@csrf
                                            <button class="btn btn-sm btn-outline-secondary">{{ $section->enable ? 'Hide' : 'Show' }}</button>
                                        </form>
                                        @if(isset($schema[$section->type]))
                                            <a href="{{ getAdminPanelUrl("/cvh-homepage/{$section->id}/edit") }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endif
                                        <form action="{{ getAdminPanelUrl("/cvh-homepage/{$section->id}/delete") }}" method="post" onsubmit="return confirm('Delete this section from the homepage? This cannot be undone.')">@csrf
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
