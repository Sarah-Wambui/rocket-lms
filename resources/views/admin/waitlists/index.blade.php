{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\WaitlistController@index.
     Lists courses that have the waitlist enabled (course editor > "Enable waitlist"); each row links to its members list. --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.waitlists') }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">{{ $pageTitle }}</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">{{ trans('update.manage_all_items_in_a_single_place') }}</p>
                    </div>

                    <div class="d-flex align-items-center gap-12">
                        @can('admin_waitlists_exports')
                            <a href="{{ getAdminPanelUrl('/waitlists/export') }}" class="btn bg-white bg-hover-gray-100 border-gray-400 text-gray-500">
                                <x-iconsax-lin-import-2 class="icons text-gray-500" width="18px" height="18px"/>
                                <span class="ml-4 font-12">{{ trans('admin/main.export_xls') }}</span>
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th class="text-left">{{ trans('admin/main.course') }}</th>
                                <th>{{ trans('update.members') }}</th>
                                <th>{{ trans('update.registered_members') }}</th>
                                <th>{{ trans('update.last_submission') }}</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($waitlists as $waitlist)
                                <tr>
                                    <td class="text-left">
                                        <a href="{{ $waitlist->getUrl() }}" target="_blank" class="font-weight-bold">{{ $waitlist->title }}</a>
                                        @if(!empty($waitlist->teacher))
                                            <div class="text-small text-gray-500">{{ $waitlist->teacher->full_name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $waitlist->members }}</td>
                                    <td>{{ $waitlist->registered_members }}</td>
                                    <td>{{ !empty($waitlist->last_submission) ? dateTimeFormat($waitlist->last_submission, 'j M Y H:i') : '-' }}</td>
                                    <td>
                                        <div class="btn-group dropdown table-actions position-relative">
                                            <button type="button" class="btn-transparent dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <x-iconsax-lin-more class="icons text-gray-500" width="20px" height="20px"/>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                @can('admin_waitlists_users')
                                                    <a href="{{ getAdminPanelUrl("/waitlists/{$waitlist->id}/view_list") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-profile-2user class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 ml-2">{{ trans('update.view_list') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_waitlists_exports')
                                                    <a href="{{ getAdminPanelUrl("/waitlists/{$waitlist->id}/export_list") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-import-2 class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 ml-2">{{ trans('update.export_list') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_waitlists_clear_list')
                                                    @include('admin.includes.delete_button', [
                                                        'url' => getAdminPanelUrl("/waitlists/{$waitlist->id}/clear_list"),
                                                        'btnClass' => 'dropdown-item text-danger mb-3 py-3 px-0 font-14',
                                                        'btnText' => trans('update.clear_list'),
                                                        'btnIcon' => 'trash',
                                                        'iconType' => 'lin',
                                                        'iconClass' => 'text-danger',
                                                    ])
                                                @endcan

                                                @can('admin_waitlists_disable')
                                                    @include('admin.includes.delete_button', [
                                                        'url' => getAdminPanelUrl("/waitlists/{$waitlist->id}/disable"),
                                                        'btnClass' => 'dropdown-item text-warning mb-0 py-3 px-0 font-14',
                                                        'btnText' => trans('update.disable_waitlist'),
                                                        'btnIcon' => 'close-circle',
                                                        'iconType' => 'lin',
                                                        'iconClass' => 'text-warning',
                                                        'deleteConfirmMsg' => trans('update.disable_waitlist') . '?',
                                                    ])
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500 py-30">{{ trans('update.waitlists') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $waitlists->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
