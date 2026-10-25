{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\UpcomingCoursesController@index --}}
@extends('admin.layouts.app')

@php
    $statusBadges = [
        \App\Models\UpcomingCourse::$active => ['success', trans('admin/main.published')],
        \App\Models\UpcomingCourse::$pending => ['warning', trans('admin/main.pending')],
        \App\Models\UpcomingCourse::$isDraft => ['secondary', trans('admin/main.is_draft')],
        \App\Models\UpcomingCourse::$inactive => ['danger', trans('admin/main.rejected')],
    ];
@endphp

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.upcoming_courses') }}</div>
            </div>
        </div>
    </section>

    <div class="row">
        @foreach([
            ['update.total_courses', $totalCourses, 'video-play'],
            ['update.released_courses', $releasedCourses, 'tick-circle'],
            ['update.not_released', $notReleased, 'timer-1'],
            ['update.total_followers', $followers, 'user'],
        ] as [$label, $value, $icon])
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card-statistic">
                    <div class="card-statistic__mask"></div>
                    <div class="card-statistic__wrap">
                        <div class="d-flex align-items-start justify-content-between">
                            <span class="text-gray-500 mt-8">{{ trans($label) }}</span>
                            <div class="d-flex-center size-48 bg-primary-30 rounded-12">
                                @svg("iconsax-bul-{$icon}", ['width' => '24px', 'height' => '24px', 'class' => 'icons text-primary'])
                            </div>
                        </div>
                        <h5 class="font-24 mt-12 line-height-1 text-black">{{ $value }}</h5>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="card mt-32">
        <div class="card-body pb-4">
            <form method="get" class="mb-0">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="input-label">{{ trans('admin/main.search') }}</label>
                            <input name="title" type="text" class="form-control" value="{{ request()->get('title') }}">
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

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="input-label">{{ trans('public.sort_by') }}</label>
                            <select name="sort" class="form-control">
                                <option value="">{{ trans('admin/main.all') }}</option>
                                @foreach(['newest', 'earliest_publish_date', 'farthest_publish_date', 'highest_price', 'lowest_price', 'only_not_released_courses'] as $sort)
                                    <option value="{{ $sort }}" @if(request()->get('sort') == $sort) selected @endif>{{ trans('update.' . $sort) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="input-label">{{ trans('admin/main.instructor') }}</label>
                            <select name="teacher_ids[]" multiple="multiple" data-search-option="just_teacher_role" class="form-control search-user-select2" data-placeholder="{{ trans('public.search_instructors') }}">
                                @if(!empty(request()->get('teacher_ids')))
                                    @foreach(\App\User::query()->whereIn('id', (array)request()->get('teacher_ids'))->get() as $teacher)
                                        <option value="{{ $teacher->id }}" selected>{{ $teacher->full_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="input-label">{{ trans('admin/main.category') }}</label>
                            <select name="category_id" class="form-control">
                                <option value="">{{ trans('admin/main.all_categories') }}</option>
                                @foreach($categories as $category)
                                    @if(!empty($category->subCategories) and count($category->subCategories))
                                        <optgroup label="{{ $category->title }}">
                                            @foreach($category->subCategories as $subCategory)
                                                <option value="{{ $subCategory->id }}" @if(request()->get('category_id') == $subCategory->id) selected @endif>{{ $subCategory->title }}</option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        <option value="{{ $category->id }}" @if(request()->get('category_id') == $category->id) selected @endif>{{ $category->title }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="input-label">{{ trans('admin/main.status') }}</label>
                            <select name="status" class="form-control">
                                <option value="">{{ trans('admin/main.all') }}</option>
                                @foreach($statusBadges as $status => [$badge, $statusLabel])
                                    <option value="{{ $status }}" @if(request()->get('status') == $status) selected @endif>{{ $statusLabel }}</option>
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

    <div class="card">
        <div class="card-header justify-content-between">
            <div>
                <h5 class="font-14 mb-0">{{ $pageTitle }}</h5>
                <p class="font-12 mt-4 mb-0 text-gray-500">{{ trans('update.manage_all_items_in_a_single_place') }}</p>
            </div>

            <div class="d-flex align-items-center gap-12">
                @can('admin_upcoming_courses_export_excel')
                    <a href="{{ getAdminPanelUrl('/upcoming_courses/excel?' . http_build_query(request()->all())) }}" class="btn bg-white bg-hover-gray-100 border-gray-400 text-gray-500">
                        <x-iconsax-lin-import-2 class="icons text-gray-500" width="18px" height="18px"/>
                        <span class="ml-4 font-12">{{ trans('admin/main.export_xls') }}</span>
                    </a>
                @endcan

                @can('admin_upcoming_courses_create')
                    <a href="{{ getAdminPanelUrl('/upcoming_courses/new') }}" class="btn btn-primary">{{ trans('update.new_upcoming_course') }}</a>
                @endcan
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table custom-table font-14">
                    <tr>
                        <th class="text-left">{{ trans('admin/main.title') }}</th>
                        <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                        <th>{{ trans('admin/main.price') }}</th>
                        <th>{{ trans('update.followers') }}</th>
                        <th>{{ trans('update.estimated_publish_date') }}</th>
                        <th>{{ trans('update.released') }}</th>
                        <th>{{ trans('admin/main.status') }}</th>
                        <th>{{ trans('admin/main.actions') }}</th>
                    </tr>

                    @forelse($upcomingCourses as $upcomingCourse)
                        <tr>
                            <td class="text-left">
                                <a href="{{ $upcomingCourse->getUrl() }}" target="_blank" class="font-weight-bold">{{ $upcomingCourse->title }}</a>
                                <div class="text-small text-gray-500">{{ !empty($upcomingCourse->category) ? $upcomingCourse->category->title : '' }}</div>
                            </td>
                            <td class="text-left">{{ !empty($upcomingCourse->teacher) ? $upcomingCourse->teacher->full_name : '-' }}</td>
                            <td>{{ !empty($upcomingCourse->price) ? handlePrice($upcomingCourse->price) : trans('public.free') }}</td>
                            <td>
                                @can('admin_upcoming_courses_followers')
                                    <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/followers") }}">{{ $upcomingCourse->followers_count }}</a>
                                @else
                                    {{ $upcomingCourse->followers_count }}
                                @endcan
                            </td>
                            <td>{{ !empty($upcomingCourse->publish_date) ? dateTimeFormat($upcomingCourse->publish_date, 'j M Y H:i') : '-' }}</td>
                            <td>
                                @if(!empty($upcomingCourse->webinar_id) and !empty($upcomingCourse->webinar))
                                    <a href="{{ $upcomingCourse->webinar->getUrl() }}" target="_blank" class="text-success">{{ trans('update.view_course') }}</a>
                                @else
                                    <span class="text-gray-500">{{ trans('update.not_released') }}</span>
                                @endif
                            </td>
                            <td>
                                @php [$badge, $statusLabel] = $statusBadges[$upcomingCourse->status] ?? ['secondary', $upcomingCourse->status]; @endphp
                                <span class="badge badge-{{ $badge }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <div class="btn-group dropdown table-actions position-relative">
                                    <button type="button" class="btn-transparent dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <x-iconsax-lin-more class="icons text-gray-500" width="20px" height="20px"/>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @can('admin_upcoming_courses_edit')
                                            <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/edit") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                <x-iconsax-lin-edit-2 class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                <span class="text-gray-500 ml-2">{{ trans('admin/main.edit') }}</span>
                                            </a>
                                        @endcan

                                        @can('admin_upcoming_courses_followers')
                                            <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/followers") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                <x-iconsax-lin-profile-2user class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                <span class="text-gray-500 ml-2">{{ trans('update.followers') }}</span>
                                            </a>
                                        @endcan

                                        @can('admin_upcoming_courses_delete')
                                            @if($upcomingCourse->status != \App\Models\UpcomingCourse::$active)
                                                <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/approve") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                    <x-iconsax-lin-tick-circle class="icons text-success mr-2" width="18px" height="18px"/>
                                                    <span class="text-success ml-2">{{ trans('admin/main.approve') }}</span>
                                                </a>
                                            @else
                                                <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/unpublish") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                    <x-iconsax-lin-eye-slash class="icons text-warning mr-2" width="18px" height="18px"/>
                                                    <span class="text-warning ml-2">{{ trans('admin/main.unpublish') }}</span>
                                                </a>
                                            @endif

                                            @if($upcomingCourse->status != \App\Models\UpcomingCourse::$inactive)
                                                <a href="{{ getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/reject") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                    <x-iconsax-lin-close-circle class="icons text-danger mr-2" width="18px" height="18px"/>
                                                    <span class="text-danger ml-2">{{ trans('admin/main.reject') }}</span>
                                                </a>
                                            @endif

                                            @include('admin.includes.delete_button', [
                                                'url' => getAdminPanelUrl("/upcoming_courses/{$upcomingCourse->id}/delete"),
                                                'btnClass' => 'dropdown-item text-danger mb-0 py-3 px-0 font-14',
                                                'btnText' => trans('admin/main.delete'),
                                                'btnIcon' => 'trash',
                                                'iconType' => 'lin',
                                                'iconClass' => 'text-danger',
                                            ])
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-gray-500 py-30">{{ trans('update.upcoming_courses') }}: 0</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </div>

        <div class="card-footer text-center">
            {{ $upcomingCourses->appends(request()->input())->links() }}
        </div>
    </div>
@endsection
