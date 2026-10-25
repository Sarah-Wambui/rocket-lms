{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\FormsController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.forms') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.forms') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header justify-content-between">
                    <div class="text-muted font-14">{{ $forms->total() }} {{ trans('update.forms') }}</div>
                    @can('admin_forms_create')
                        <a href="{{ getAdminPanelUrl('/forms/create') }}" class="btn btn-primary">{{ trans('update.new_form') }}</a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                <th>{{ trans('admin/main.url') }}</th>
                                <th>{{ trans('update.inputs') }}</th>
                                <th>{{ trans('update.submissions') }}</th>
                                <th>{{ trans('admin/main.users') }}</th>
                                <th>{{ trans('admin/main.start_date') }}</th>
                                <th>{{ trans('admin/main.end_date') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($forms as $form)
                                <tr>
                                    <td class="text-left">
                                        <div class="font-weight-bold">{{ $form->title }}</div>
                                        @if(!empty($form->subtitle))
                                            <div class="text-muted font-12">{{ truncate($form->subtitle, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="/forms/{{ $form->url }}" target="_blank">/forms/{{ $form->url }}</a>
                                    </td>
                                    <td>{{ $form->fields_count }}</td>
                                    <td>{{ $form->submissions_count }}</td>
                                    <td>{{ $form->users_count }}</td>
                                    <td>{{ !empty($form->start_date) ? dateTimeFormat($form->start_date, 'j M Y H:i') : '-' }}</td>
                                    <td>{{ !empty($form->end_date) ? dateTimeFormat($form->end_date, 'j M Y H:i') : '-' }}</td>
                                    <td>
                                        @if($form->enable)
                                            <span class="text-success">{{ trans('admin/main.active') }}</span>
                                        @else
                                            <span class="text-warning">{{ trans('public.disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="/forms/{{ $form->url }}" target="_blank" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('update.preview') }}">
                                            <i class="fa fa-eye"></i>
                                        </a>

                                        @can('admin_forms_submissions')
                                            <a href="{{ getAdminPanelUrl('/forms/submissions?form=' . $form->id) }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('update.submissions') }}">
                                                <i class="fa fa-list"></i>
                                            </a>
                                        @endcan

                                        @can('admin_forms_edit')
                                            <a href="{{ getAdminPanelUrl("/forms/{$form->id}/edit") }}" class="btn-transparent btn-sm text-primary mr-1" data-toggle="tooltip" data-placement="top" title="{{ trans('admin/main.edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan

                                        @can('admin_forms_delete')
                                            @include('admin.includes.delete_button',['url' => getAdminPanelUrl("/forms/{$form->id}/delete"), 'btnClass' => 'btn-sm'])
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-30">{{ trans('update.forms') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $forms->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
