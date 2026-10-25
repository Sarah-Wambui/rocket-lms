{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\CoursePersonalNotesController@index.
     View / edit pop-ups are handled by the template's /assets/admin/js/parts/personal_note.min.js (.js-show-note, .js-edit-note). --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.course_notes') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.course_notes') }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body pb-4">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('public.search') }}</label>
                                    <input type="text" class="form-control" name="search" value="{{ request()->get('search') }}" placeholder="{{ trans('admin/main.class') }}">
                                </div>
                            </div>

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

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('update.content_type') }}</label>
                                    <select name="content_type" class="form-control">
                                        <option value="">{{ trans('public.all') }}</option>
                                        @foreach(['webinar', 'course', 'text_lesson'] as $contentType)
                                            <option value="{{ $contentType }}" {{ request()->get('content_type') == $contentType ? 'selected' : '' }}>{{ trans("webinars.{$contentType}") }}</option>
                                        @endforeach
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
                                <th class="text-left">{{ trans('admin/main.student') }}</th>
                                <th class="text-left">{{ trans('admin/main.class') }}</th>
                                <th class="text-left">{{ trans('update.content_type') }}</th>
                                <th>{{ trans('update.note') }}</th>
                                @if(!empty(getFeaturesSettings('course_notes_attachment')))
                                    <th>{{ trans('update.attachment') }}</th>
                                @endif
                                <th>{{ trans('admin/main.date') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($personalNotes as $personalNote)
                                @php
                                    try {
                                        $item = $personalNote->getItem();
                                    } catch (\Throwable $exception) {
                                        $item = null;
                                    }
                                @endphp
                                <tr class="text-center">
                                    <td class="text-left">
                                        @if(!empty($personalNote->user))
                                            <div class="user-inline-avatar d-flex align-items-center">
                                                <div class="avatar bg-gray200">
                                                    <img src="{{ $personalNote->user->getAvatar() }}" class="img-cover" alt="">
                                                </div>
                                                <div class="ml-1">
                                                    <span class="d-block font-weight-500">{{ $personalNote->user->full_name ?: '-' }}</span>
                                                    <span class="mt-1 font-12 text-gray-500 d-block">{{ $personalNote->user->email }}</span>
                                                </div>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="text-left">
                                        @if(!empty($personalNote->course))
                                            <a href="{{ $personalNote->course->getUrl() }}" target="_blank" class="text-dark">{{ $personalNote->course->title }}</a>
                                            @if(!empty($personalNote->course->teacher))
                                                <span class="d-block font-12 text-gray-500 mt-4">{{ trans('public.by') }} {{ $personalNote->course->teacher->full_name }}</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td class="text-left">
                                        @if(!empty($item))
                                            <span class="d-block">{{ $item->title }}</span>
                                            <span class="d-block font-12 text-gray-500 mt-4">
                                                {{ trans('update.' . $personalNote->getItemType()) }}
                                                @if(!empty($item->chapter))
                                                    | {{ $item->chapter->title }}
                                                @endif
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <input type="hidden" value="{{ nl2br(e($personalNote->note)) }}">
                                        <button type="button" class="js-show-note btn btn-sm btn-outline-primary">{{ trans('admin/main.show') }}</button>
                                    </td>

                                    @if(!empty(getFeaturesSettings('course_notes_attachment')))
                                        <td>
                                            @if(!empty($personalNote->attachment))
                                                <a href="{{ getAdminPanelUrl("/webinars/personal-notes/{$personalNote->id}/download-attachment") }}" class="btn btn-sm btn-outline-primary">{{ trans('home.download') }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif

                                    <td>{{ dateTimeFormat($personalNote->created_at, 'j M Y | H:i') }}</td>

                                    <td>
                                        <input type="hidden" class="js-note-message" value="{{ $personalNote->note }}">
                                        <input type="hidden" class="js-note-attachment" value="{{ $personalNote->attachment }}">

                                        <div class="btn-group dropdown table-actions position-relative">
                                            <button type="button" class="btn-transparent dropdown-toggle" data-toggle="dropdown">
                                                <x-iconsax-lin-more class="icons text-gray-500" width="20px" height="20px"/>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-right">
                                                @if(!empty($personalNote->course) and !empty($item))
                                                    <a href="{{ "{$personalNote->course->getLearningPageUrl()}?type={$personalNote->getItemType()}&item={$personalNote->targetable_id}" }}" target="_blank" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-eye class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 font-14">{{ trans('public.view') }}</span>
                                                    </a>
                                                @endif

                                                <button type="button" data-action="{{ getAdminPanelUrl("/webinars/personal-notes/{$personalNote->id}/update") }}" class="js-edit-note dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4 btn-transparent">
                                                    <x-iconsax-lin-edit-2 class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                    <span class="text-gray-500 font-14">{{ trans('admin/main.edit') }}</span>
                                                </button>

                                                @include('admin.includes.delete_button', [
                                                    'url' => getAdminPanelUrl("/webinars/personal-notes/{$personalNote->id}/delete"),
                                                    'btnClass' => 'dropdown-item text-danger mb-0 py-3 px-0 font-14 d-flex align-items-center gap-4',
                                                    'btnText' => trans('admin/main.delete'),
                                                    'btnIcon' => 'trash',
                                                    'iconType' => 'lin',
                                                    'iconClass' => 'text-danger mr-2',
                                                ])
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-gray-500 py-30">{{ trans('update.no_data') }}</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $personalNotes->appends(request()->input())->links() }}
                </div>
            </section>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script>
        var noteLang = '{{ trans('update.note') }}';
        var personalNoteLang = '{{ trans('update.personal_note') }}';
        var attachmentLang = '{{ trans('update.attachment') }}';
        var saveNoteLang = '{{ trans('update.save_note') }}';
        var closeLang = '{{ trans('public.close') }}';
    </script>
    <script src="/assets/admin/js/parts/personal_note.min.js"></script>
@endpush
