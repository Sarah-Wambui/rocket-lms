{{-- CVH: rebuilt (the template shipped a "paid plugin" placeholder here); data from Admin\BundleController@index --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ trans('update.bundles') }} {{ trans('admin/main.list') }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ trans('update.bundles') }}</div>
            </div>
        </div>

        <div class="section-body">

            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card-statistic">
                        <div class="card-statistic__mask"></div>
                        <div class="card-statistic__wrap">
                            <div class="d-flex align-items-start justify-content-between">
                                <span class="text-gray-500 mt-8">{{ trans('update.total_bundles') }}</span>
                                <div class="d-flex-center size-48 bg-primary-30 rounded-12">
                                    <x-iconsax-bul-box class="icons text-primary" width="24px" height="24px"/>
                                </div>
                            </div>
                            <h5 class="font-24 mt-12 line-height-1 text-black">{{ $totalBundles }}</h5>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card-statistic">
                        <div class="card-statistic__mask"></div>
                        <div class="card-statistic__wrap">
                            <div class="d-flex align-items-start justify-content-between">
                                <span class="text-gray-500 mt-8">{{ trans('admin/main.pending_review') }}</span>
                                <div class="d-flex-center size-48 bg-warning-30 rounded-12">
                                    <x-iconsax-bul-timer class="icons text-warning" width="24px" height="24px"/>
                                </div>
                            </div>
                            <h5 class="font-24 mt-12 line-height-1 text-black">{{ $totalPendingBundles }}</h5>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                    <div class="card-statistic">
                        <div class="card-statistic__mask"></div>
                        <div class="card-statistic__wrap">
                            <div class="d-flex align-items-start justify-content-between">
                                <span class="text-gray-500 mt-8">{{ trans('admin/main.total_sales') }}</span>
                                <div class="d-flex-center size-48 bg-success-30 rounded-12">
                                    <x-iconsax-bul-bag class="icons text-success" width="24px" height="24px"/>
                                </div>
                            </div>
                            <h5 class="font-24 mt-12 line-height-1 text-black">{{ !empty($totalSales) ? (int)$totalSales->sales_count : 0 }}</h5>
                            @if(!empty($totalSales) and $totalSales->total_amount > 0)
                                <div class="font-12 text-gray-500 mt-4">{{ handlePrice($totalSales->total_amount) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
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

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.start_date') }}</label>
                                    <input type="date" class="text-center form-control" name="from" value="{{ request()->get('from') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.end_date') }}</label>
                                    <input type="date" class="text-center form-control" name="to" value="{{ request()->get('to') }}">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.filters') }}</label>
                                    <select name="sort" data-plugin-selectTwo class="form-control populate">
                                        <option value="">{{ trans('admin/main.filter_type') }}</option>
                                        <option value="has_discount" @if(request()->get('sort') == 'has_discount') selected @endif>{{ trans('admin/main.discounted_classes') }}</option>
                                        <option value="sales_asc" @if(request()->get('sort') == 'sales_asc') selected @endif>{{ trans('admin/main.sales_ascending') }}</option>
                                        <option value="sales_desc" @if(request()->get('sort') == 'sales_desc') selected @endif>{{ trans('admin/main.sales_descending') }}</option>
                                        <option value="price_asc" @if(request()->get('sort') == 'price_asc') selected @endif>{{ trans('admin/main.Price_ascending') }}</option>
                                        <option value="price_desc" @if(request()->get('sort') == 'price_desc') selected @endif>{{ trans('admin/main.Price_descending') }}</option>
                                        <option value="income_asc" @if(request()->get('sort') == 'income_asc') selected @endif>{{ trans('admin/main.Income_ascending') }}</option>
                                        <option value="income_desc" @if(request()->get('sort') == 'income_desc') selected @endif>{{ trans('admin/main.Income_descending') }}</option>
                                        <option value="created_at_asc" @if(request()->get('sort') == 'created_at_asc') selected @endif>{{ trans('admin/main.create_date_ascending') }}</option>
                                        <option value="created_at_desc" @if(request()->get('sort') == 'created_at_desc') selected @endif>{{ trans('admin/main.create_date_descending') }}</option>
                                        <option value="updated_at_asc" @if(request()->get('sort') == 'updated_at_asc') selected @endif>{{ trans('admin/main.update_date_ascending') }}</option>
                                        <option value="updated_at_desc" @if(request()->get('sort') == 'updated_at_desc') selected @endif>{{ trans('admin/main.update_date_descending') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.instructor') }}</label>
                                    <select name="teacher_ids[]" multiple="multiple" data-search-option="just_teacher_role" class="form-control search-user-select2" data-placeholder="{{ trans('public.search_instructor') }}">
                                        @if(!empty($teachers) and $teachers->count() > 0)
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" selected>{{ $teacher->full_name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.category') }}</label>
                                    <select name="category_id" data-plugin-selectTwo class="form-control populate">
                                        <option value="">{{ trans('admin/main.all_categories') }}</option>
                                        @foreach($categories as $category)
                                            @if(!empty($category->subCategories) and count($category->subCategories))
                                                <optgroup label="{{ $category->title }}">
                                                    @foreach($category->subCategories as $subCategory)
                                                        <option value="{{ $subCategory->id }}" @if(request()->get('category_id') == $subCategory->id) selected="selected" @endif>{{ $subCategory->title }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @else
                                                <option value="{{ $category->id }}" @if(request()->get('category_id') == $category->id) selected="selected" @endif>{{ $category->title }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="input-label">{{ trans('admin/main.status') }}</label>
                                    <select name="status" data-plugin-selectTwo class="form-control populate">
                                        <option value="">{{ trans('admin/main.all_status') }}</option>
                                        <option value="pending" @if(request()->get('status') == 'pending') selected @endif>{{ trans('admin/main.pending_review') }}</option>
                                        <option value="active" @if(request()->get('status') == 'active') selected @endif>{{ trans('admin/main.published') }}</option>
                                        <option value="inactive" @if(request()->get('status') == 'inactive') selected @endif>{{ trans('admin/main.rejected') }}</option>
                                        <option value="is_draft" @if(request()->get('status') == 'is_draft') selected @endif>{{ trans('admin/main.draft') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 d-flex align-items-center">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">{{ trans('admin/main.show_results') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <div class="card">
                <div class="card-header justify-content-between">
                    <div>
                        <h5 class="font-14 mb-0">{{ trans('update.bundles') }}</h5>
                        <p class="font-12 mt-4 mb-0 text-gray-500">{{ trans('update.manage_all_bundles_in_a_single_place') }}</p>
                    </div>

                    @can('admin_bundles_create')
                        <a href="{{ getAdminPanelUrl('/bundles/create') }}" class="btn btn-primary">
                            <x-iconsax-lin-add class="icons text-white" width="18px" height="18px"/>
                            <span class="ml-4 font-12">{{ trans('update.new_bundle') }}</span>
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table font-14">
                            <tr>
                                <th>{{ trans('admin/main.id') }}</th>
                                <th class="text-left">{{ trans('admin/main.title') }}</th>
                                <th class="text-left">{{ trans('admin/main.instructor') }}</th>
                                <th>{{ trans('update.courses') }}</th>
                                <th>{{ trans('admin/main.price') }}</th>
                                <th>{{ trans('admin/main.sales') }}</th>
                                <th>{{ trans('admin/main.income') }}</th>
                                <th>{{ trans('admin/main.created_at') }}</th>
                                <th>{{ trans('admin/main.updated_at') }}</th>
                                <th>{{ trans('admin/main.status') }}</th>
                                <th width="120">{{ trans('admin/main.actions') }}</th>
                            </tr>

                            @forelse($bundles as $bundle)
                                <tr class="text-center">
                                    <td>{{ $bundle->id }}</td>
                                    <td width="18%" class="text-left">
                                        <a class="text-dark mt-0 mb-1" href="{{ $bundle->getUrl() }}" target="_blank">{{ $bundle->title }}</a>
                                        @if(!empty($bundle->category->title))
                                            <div class="text-small text-gray-500">{{ $bundle->category->title }}</div>
                                        @else
                                            <div class="text-small text-warning">{{ trans('admin/main.no_category') }}</div>
                                        @endif
                                    </td>

                                    <td class="text-left">{{ !empty($bundle->teacher) ? $bundle->teacher->full_name : '-' }}</td>

                                    <td>{{ $bundle->bundle_webinars_count }}</td>

                                    <td>
                                        @if(!empty($bundle->price) and $bundle->price > 0)
                                            {{ handlePrice($bundle->price, true, true) }}
                                        @else
                                            {{ trans('public.free') }}
                                        @endif
                                    </td>

                                    <td>
                                        @can('admin_webinar_students_lists')
                                            <a href="{{ getAdminPanelUrl("/bundles/{$bundle->id}/students") }}" class="text-dark">{{ $bundle->sales->count() }}</a>
                                        @else
                                            {{ $bundle->sales->count() }}
                                        @endcan
                                    </td>

                                    <td>{{ handlePrice($bundle->sales->sum('total_amount')) }}</td>

                                    <td>{{ dateTimeFormat($bundle->created_at, 'Y M j | H:i') }}</td>
                                    <td>{{ !empty($bundle->updated_at) ? dateTimeFormat($bundle->updated_at, 'Y M j | H:i') : '-' }}</td>

                                    <td>
                                        @switch($bundle->status)
                                            @case(\App\Models\Bundle::$active)
                                                <div class="badge-status text-success bg-success-30">{{ trans('admin/main.published') }}</div>
                                                @break
                                            @case(\App\Models\Bundle::$isDraft)
                                                <span class="badge-status text-dark bg-dark-30">{{ trans('admin/main.is_draft') }}</span>
                                                @break
                                            @case(\App\Models\Bundle::$pending)
                                                <span class="badge-status text-warning bg-warning-30">{{ trans('admin/main.waiting') }}</span>
                                                @break
                                            @case(\App\Models\Bundle::$inactive)
                                                <span class="badge-status text-danger bg-danger-30">{{ trans('public.rejected') }}</span>
                                                @break
                                        @endswitch
                                    </td>

                                    <td>
                                        <div class="btn-group dropdown table-actions position-relative">
                                            <button type="button" class="btn-transparent dropdown-toggle" data-toggle="dropdown">
                                                <x-iconsax-lin-more class="icons text-gray-500" width="20px" height="20px"/>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-right">
                                                @can('admin_bundles_edit')
                                                    @if(in_array($bundle->status, [\App\Models\Bundle::$pending, \App\Models\Bundle::$inactive, \App\Models\Bundle::$isDraft]))
                                                        @include('admin.includes.delete_button',[
                                                            'url' => getAdminPanelUrl("/bundles/{$bundle->id}/approve"),
                                                            'btnClass' => 'dropdown-item text-success mb-3 py-3 px-0 font-14',
                                                            'btnText' => trans('admin/main.approve'),
                                                            'btnIcon' => 'tick-square',
                                                            'iconType' => 'lin',
                                                            'iconClass' => 'text-success mr-2',
                                                        ])
                                                    @endif

                                                    @if($bundle->status == \App\Models\Bundle::$pending)
                                                        @include('admin.includes.delete_button',[
                                                            'url' => getAdminPanelUrl("/bundles/{$bundle->id}/reject"),
                                                            'btnClass' => 'dropdown-item text-danger mb-3 py-3 px-0 font-14',
                                                            'btnText' => trans('admin/main.reject'),
                                                            'btnIcon' => 'close-square',
                                                            'iconType' => 'lin',
                                                            'iconClass' => 'text-danger mr-2',
                                                        ])
                                                    @endif

                                                    @if($bundle->status == \App\Models\Bundle::$active)
                                                        @include('admin.includes.delete_button',[
                                                            'url' => getAdminPanelUrl("/bundles/{$bundle->id}/unpublish"),
                                                            'btnClass' => 'dropdown-item text-danger mb-3 py-3 px-0 font-14',
                                                            'btnText' => trans('admin/main.unpublish'),
                                                            'btnIcon' => 'gallery-slash',
                                                            'iconType' => 'lin',
                                                            'iconClass' => 'text-danger mr-2',
                                                        ])
                                                    @endif
                                                @endcan

                                                @can('admin_webinar_notification_to_students')
                                                    <a href="{{ getAdminPanelUrl("/bundles/{$bundle->id}/sendNotification") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-notification-bing class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 font-14">{{ trans('notification.send_notification') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_webinar_students_lists')
                                                    <a href="{{ getAdminPanelUrl("/bundles/{$bundle->id}/students") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-teacher class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 font-14">{{ trans('admin/main.students') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_bundles_edit')
                                                    <a href="{{ getAdminPanelUrl("/bundles/{$bundle->id}/edit") }}" class="dropdown-item d-flex align-items-center mb-3 py-3 px-0 gap-4">
                                                        <x-iconsax-lin-edit-2 class="icons text-gray-500 mr-2" width="18px" height="18px"/>
                                                        <span class="text-gray-500 font-14">{{ trans('admin/main.edit') }}</span>
                                                    </a>
                                                @endcan

                                                @can('admin_bundles_delete')
                                                    @include('admin.includes.delete_button',[
                                                        'url' => getAdminPanelUrl("/bundles/{$bundle->id}/delete"),
                                                        'btnClass' => 'dropdown-item text-danger mb-0 py-3 px-0 font-14',
                                                        'btnText' => trans('admin/main.delete'),
                                                        'btnIcon' => 'trash',
                                                        'iconType' => 'lin',
                                                        'iconClass' => 'text-danger mr-2',
                                                    ])
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-gray-500 py-30">{{ trans('update.bundles') }}: 0</td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="card-footer text-center">
                    {{ $bundles->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
