{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\NoticeboardController@index
     (site notices) and Admin\CourseNoticeboardController@index (course notices, $isCourseNotice = true) --}}
@extends('admin.layouts.app')

@php
    $isCourseNotice = !empty($isCourseNotice);
    $basePath = $isCourseNotice ? '/course-noticeboards' : '/noticeboards';
    $permissionPrefix = $isCourseNotice ? 'admin_course_noticeboards' : 'admin_noticeboards';
    $colorClasses = ['warning' => 'warning', 'danger' => 'danger', 'neutral' => 'secondary', 'info' => 'info', 'success' => 'success'];
    $typeLabel = fn($type) => $type === 'organizations' ? trans('admin/main.organizations') : trans('public.' . $type);
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.search') }}</label>
                                    <input type="text" class="form-control" name="search" value="{{ request()->get('search') }}">
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

                            @if($isCourseNotice)
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.sender') }}</label>
                                        <select name="sender_id" class="form-control search-user-select2" data-search-option="just_teacher_role" data-placeholder="{{ trans('public.search_user') }}">
                                            @if(!empty($sender))
                                                <option value="{{ $sender->id }}" selected>{{ $sender->full_name }}</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('update.color') }}</label>
                                        <select name="color" class="form-control">
                                            <option value="">{{ trans('admin/main.all') }}</option>
                                            @foreach(\App\Models\CourseNoticeboard::$colors as $color)
                                                <option value="{{ $color }}" @if(request()->get('color') == $color) selected @endif>{{ trans('update.course_noticeboard_color_' . $color) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.sender') }}</label>
                                        <select name="sender" class="form-control">
                                            <option value="">{{ trans('admin/main.all') }}</option>
                                            <option value="admin" @if(request()->get('sender') == 'admin') selected @endif>{{ trans('admin/main.admin') }}</option>
                                            <option value="organizations" @if(request()->get('sender') == 'organizations') selected @endif>{{ trans('admin/main.organizations') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{ trans('admin/main.type') }}</label>
                                        <select name="type" class="form-control">
                                            <option value="">{{ trans('admin/main.all') }}</option>
                                            @foreach(\App\Models\Noticeboard::$migrateTypes as $type)
                                                <option value="{{ $type }}" @if(request()->get('type') == $type) selected @endif>{{ $typeLabel($type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-2 d-flex align-items-center">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">{{ trans('admin/main.show_results') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">{{ trans('admin/main.notices_list_title') }}</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">{{ $noticeboards->total() }} {{ $pageTitle }}</p>
                    </div>

                    @can($permissionPrefix . '_send')
                        <a href="{{ getAdminPanelUrl($basePath . '/send') }}" class="btn btn-primary">{{ trans('admin/main.new_notice_title') }}</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                @if($isCourseNotice)
                                    <th class="text-left">{{ trans('admin/main.course') }}</th>
                                    <th>{{ trans('admin/main.sender') }}</th>
                                    <th>{{ trans('update.color') }}</th>
                                @else
                                    <th>{{ trans('admin/main.sender') }}</th>
                                    <th>{{ trans('admin/main.type') }}</th>
                                @endif
                                <th>{{ trans('admin/main.created_at') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($noticeboards as $noticeboard)
                                <tr>
                                    <td class="text-left">
                                        <span class="font-weight-bold">{{ $noticeboard->title }}</span>
                                        <a href="#" class="js-show-noticeboard-message d-block font-12 text-primary" data-target="#noticeboardMessage{{ $noticeboard->id }}">{{ trans('site.message') }}</a>
                                        <div id="noticeboardMessage{{ $noticeboard->id }}" class="d-none">{!! clean($noticeboard->message) !!}</div>
                                    </td>

                                    @if($isCourseNotice)
                                        <td class="text-left">
                                            @if(!empty($noticeboard->webinar))
                                                <a href="{{ $noticeboard->webinar->getUrl() }}" target="_blank">{{ $noticeboard->webinar->title }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ !empty($noticeboard->creator) ? $noticeboard->creator->full_name : '-' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $colorClasses[$noticeboard->color] ?? 'secondary' }}">{{ trans('update.course_noticeboard_color_' . $noticeboard->color) }}</span>
                                        </td>
                                    @else
                                        <td>
                                            @if(!empty($noticeboard->organ_id) and !empty($noticeboard->sender))
                                                {{ $noticeboard->sender }}
                                            @else
                                                {{ trans('admin/main.admin') }}
                                            @endif
                                        </td>
                                        <td>{{ $typeLabel($noticeboard->type) }}</td>
                                    @endif

                                    <td>{{ dateTimeFormat($noticeboard->created_at, 'j M Y | H:i') }}</td>

                                    <td>
                                        @can($permissionPrefix . '_edit')
                                            <a href="{{ getAdminPanelUrl($basePath . '/' . $noticeboard->id . '/edit') }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can($permissionPrefix . '_delete')
                                            @include('admin.includes.delete_button', ['url' => getAdminPanelUrl($basePath . '/' . $noticeboard->id . '/delete'), 'btnClass' => 'btn-sm'])
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isCourseNotice ? 6 : 5 }}" class="text-center text-gray-500 py-30">{{ $pageTitle }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $noticeboards->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="noticeboardMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts_bottom')
    <script>
        (function ($) {
            "use strict";

            $('body').on('click', '.js-show-noticeboard-message', function (e) {
                e.preventDefault();
                var $row = $(this).closest('td');
                var $modal = $('#noticeboardMessageModal');
                $modal.find('.modal-title').text($row.find('.font-weight-bold').first().text());
                $modal.find('.modal-body').html($($(this).attr('data-target')).html());
                $modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
