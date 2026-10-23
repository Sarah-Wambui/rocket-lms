{{-- CVH Homepage Editor: edit one section --}}
@extends('admin.layouts.app')

@push('styles_top')
    <style>
        .cvh-group { border: 1px solid #e9edf3; border-radius: 12px; padding: 12px 16px 4px; }
        .cvh-group legend { width: auto; padding: 0 6px; margin-bottom: 4px; }
        .cvh-item { position: relative; border: 1px dashed #cdd5e2; border-radius: 12px; padding: 12px 40px 4px 12px; margin-bottom: 10px; background: #fafcff; }
        .cvh-item .cvh-group { border: 0; padding: 0; }
        .cvh-item .cvh-group legend { display: none; }
        .cvh-remove { position: absolute; top: 8px; right: 8px; width: 26px; height: 26px; border-radius: 8px; border: 0; background: #fde8e8; color: #f63c3c; font-size: 18px; line-height: 1; }
        .cvh-preview { width: 96px; height: 64px; border-radius: 8px; background: #f0f4f9; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .cvh-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    </style>
@endpush

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl('/cvh-homepage') }}">Homepage Editor</a></div>
                <div class="breadcrumb-item active">{{ $schema['title'] }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('cvh_homepage_status'))
                <div class="alert alert-success">{{ session('cvh_homepage_status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form action="{{ getAdminPanelUrl("/cvh-homepage/{$section->id}/update") }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="card">
                    <div class="card-header justify-content-between">
                        <div>
                            <h5 class="font-14 mb-0">{{ $schema['title'] }}</h5>
                            <p class="font-12 mt-4 mb-0 text-gray-500">Position {{ $section->order }} on the homepage · {{ $section->enable ? 'Visible' : 'Hidden' }}</p>
                        </div>
                        <div class="d-flex gap-8">
                            <a href="/" target="_blank" class="btn btn-outline-primary">View homepage</a>
                            <button type="submit" class="btn btn-primary">Save section</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($schema['fields'] as $key => $field)
                            @include('admin.cvh_homepage.field', ['field' => $field, 'key' => $key, 'name' => "c[{$key}]", 'path' => $key, 'value' => $content[$key] ?? null, 'sources' => $sources, 'depth' => 0])
                        @endforeach
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ getAdminPanelUrl('/cvh-homepage') }}" class="btn btn-outline-secondary">Back to sections</a>
                        <button type="submit" class="btn btn-primary">Save section</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts_bottom')
    <script>
        document.addEventListener('click', function (e) {
            const add = e.target.closest('[data-cvh-add]');
            if (add) {
                const box = add.closest('.cvh-repeater');
                const tpl = box.querySelector(':scope > template');
                const key = 'new_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
                const html = tpl.innerHTML.split(add.getAttribute('data-cvh-add')).join(key);
                box.querySelector(':scope > .cvh-items').insertAdjacentHTML('beforeend', html);
                return;
            }
            const remove = e.target.closest('[data-cvh-remove]');
            if (remove && confirm('Remove this item?')) {
                remove.closest('.cvh-item').remove();
            }
        });
    </script>
@endpush
