{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\FormSubmissionsController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.submissions') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/forms') }}">{{ trans('update.forms') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.submissions') }}</div>
            </div>
        </div>

        <div class="section-body">
            <section class="card">
                <div class="card-body">
                    <form method="get" class="mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('update.form') }}</label>
                                    <select name="form" class="form-control">
                                        <option value="">{{ trans('admin/main.all') }}</option>
                                        @foreach($forms as $formItem)
                                            <option value="{{ $formItem->id }}" @if(request()->get('form') == $formItem->id) selected @endif>{{ $formItem->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.users') }}</label>
                                    <select name="user_ids[]" multiple="multiple" class="form-control search-user-select2" data-placeholder="{{ trans('public.search_user') }}">
                                        @if(!empty(request()->get('user_ids')))
                                            @foreach(\App\User::query()->whereIn('id', (array)request()->get('user_ids'))->get() as $selectedUser)
                                                <option value="{{ $selectedUser->id }}" selected>{{ $selectedUser->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.from') }}</label>
                                    <input type="date" class="form-control" name="from" value="{{ request()->get('from') }}">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.to') }}</label>
                                    <input type="date" class="form-control" name="to" value="{{ request()->get('to') }}">
                                </div>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary w-100">{{ trans('admin/main.show_results') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <div class="card">
                <div class="card-header">
                    <div class="text-muted font-14">{{ $submissions->total() }} {{ trans('update.submissions') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.user') }}</th>
                                <th class="text-left">{{ trans('update.form') }}</th>
                                <th>{{ trans('admin/main.date') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($submissions as $submission)
                                <tr>
                                    <td class="text-left">
                                        @if(!empty($submission->user))
                                            <div class="d-flex align-items-center">
                                                <figure class="avatar mr-2">
                                                    <img src="{{ $submission->user->getAvatar() }}" alt="{{ $submission->user->full_name }}">
                                                </figure>
                                                <div class="media-body ml-1">
                                                    <div class="mt-0 mb-1 font-weight-bold">{{ $submission->user->full_name }}</div>
                                                    <div class="text-primary text-small font-600-bold">{{ $submission->user->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">Guest</span>
                                        @endif
                                    </td>
                                    <td class="text-left">{{ !empty($submission->form) ? $submission->form->title : '-' }}</td>
                                    <td>{{ dateTimeFormat($submission->created_at, 'j M Y H:i') }}</td>
                                    <td>
                                        <a href="{{ getAdminPanelUrl("/forms/submissions/{$submission->id}/show") }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('update.submission_details') }}">
                                            <i class="fa fa-eye"></i>
                                        </a>

                                        @include('admin.includes.delete_button',['url' => getAdminPanelUrl("/forms/submissions/{$submission->id}/delete"), 'btnClass' => 'btn-sm'])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-30">{{ trans('update.submissions') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $submissions->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
