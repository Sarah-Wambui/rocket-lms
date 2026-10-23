{{--
    CVH Homepage Editor: renders one field (recursive for group / repeater / list).
    Params: $field (schema), $key, $name (form name, e.g. c[main_content][title]), $path (dotted path for uploads),
            $value, $sources, $depth (nesting level for template placeholders)
--}}
@php
    $type = $field['type'];
    $label = $field['label'] ?? \Illuminate\Support\Str::headline($key);
    $depth = $depth ?? 0;
    $placeholder = "__KEY{$depth}__";
@endphp

@if($type === 'group')
    <fieldset class="cvh-group mb-16">
        <legend class="font-14 font-weight-bold">{{ $label }}</legend>
        @foreach($field['fields'] as $childKey => $child)
            @include('admin.cvh_homepage.field', ['field' => $child, 'key' => $childKey, 'name' => "{$name}[{$childKey}]", 'path' => "{$path}.{$childKey}", 'value' => is_array($value) ? ($value[$childKey] ?? null) : null, 'depth' => $depth])
        @endforeach
    </fieldset>

@elseif($type === 'repeater' or $type === 'list')
    @php
        $items = collect(is_array($value) ? $value : [])->reject(fn($v, $k) => $k === 'record');
        $itemSchema = $type === 'repeater' ? ['type' => 'group', 'fields' => $field['fields']] : $field['item'];
    @endphp
    <div class="cvh-repeater mb-16">
        <div class="d-flex align-items-center justify-content-between mb-8">
            <label class="font-14 font-weight-bold mb-0">{{ $label }}</label>
            <button type="button" class="btn btn-sm btn-outline-primary" data-cvh-add="{{ $placeholder }}">+ Add</button>
        </div>
        @if(!empty($field['help']))<div class="font-12 text-gray-500 mb-8">{{ $field['help'] }}</div>@endif

        <div class="cvh-items">
            @foreach($items as $itemKey => $itemValue)
                <div class="cvh-item">
                    <button type="button" class="cvh-remove" data-cvh-remove title="Remove">&times;</button>
                    @include('admin.cvh_homepage.field', ['field' => $itemSchema + ['label' => ($type === 'repeater' ? 'Item' : '')], 'key' => $itemKey, 'name' => "{$name}[{$itemKey}]", 'path' => "{$path}.{$itemKey}", 'value' => $itemValue, 'depth' => $depth + 1])
                </div>
            @endforeach
        </div>

        <template>
            <div class="cvh-item">
                <button type="button" class="cvh-remove" data-cvh-remove title="Remove">&times;</button>
                @include('admin.cvh_homepage.field', ['field' => $itemSchema + ['label' => ($type === 'repeater' ? 'New item' : '')], 'key' => $placeholder, 'name' => "{$name}[{$placeholder}]", 'path' => "{$path}.{$placeholder}", 'value' => null, 'depth' => $depth + 1])
            </div>
        </template>
    </div>

@else
    <div class="form-group mb-12">
        @if($label !== '')<label class="font-13 mb-4">{{ $label }}</label>@endif

        @switch($type)
            @case('textarea')
                <textarea name="{{ $name }}" rows="3" class="form-control">{{ $value }}</textarea>
                @break

            @case('number')
                <input type="number" name="{{ $name }}" value="{{ $value }}" class="form-control">
                @break

            @case('toggle')
                <div class="custom-control custom-switch">
                    <input type="checkbox" name="{{ $name }}" value="on" class="custom-control-input" id="sw{{ md5($name) }}" {{ !empty($value) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sw{{ md5($name) }}">On</label>
                </div>
                @break

            @case('select')
                @php $options = $field['options'] ?? ($sources[$field['source']] ?? []); @endphp
                <select name="{{ $name }}" class="form-control">
                    <option value="">— choose —</option>
                    @foreach($options as $optValue => $optLabel)
                        <option value="{{ $optValue }}" {{ (string)$value === (string)$optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                    @endforeach
                    @if($value !== null and $value !== '' and !array_key_exists((string)$value, $options))
                        <option value="{{ $value }}" selected>{{ $value }} (not available any more)</option>
                    @endif
                </select>
                @break

            @case('image')
                <div class="d-flex align-items-start gap-12">
                    <div class="cvh-preview">@if($value)<img src="{{ $value }}" alt="">@endif</div>
                    <div class="flex-grow-1">
                        <input type="text" name="{{ $name }}" value="{{ $value }}" class="form-control form-control-sm" placeholder="/store/... (image path)">
                        <input type="file" name="f[{{ $path }}]" accept="image/png,image/jpeg,image/webp,image/gif" class="form-control-file mt-8">
                        <div class="font-11 text-gray-500 mt-4">Upload a new image to replace it (JPG, PNG, WebP or GIF, max 5 MB), or clear the path to remove it.</div>
                    </div>
                </div>
                @break

            @case('icon')
                <input type="text" name="{{ $name }}" value="{{ $value }}" class="form-control" placeholder="e.g. bul-teacher">
                <div class="font-11 text-gray-500 mt-4">Iconsax icon name (bul-, bol- or lin- + name, e.g. bul-video-play).</div>
                @break

            @default
                <input type="{{ $type === 'url' ? 'text' : 'text' }}" name="{{ $name }}" value="{{ $value }}" class="form-control" @if($type === 'url') placeholder="/classes or https://..." @endif>
        @endswitch

        @if(!empty($field['help']))<div class="font-11 text-gray-500 mt-4">{{ $field['help'] }}</div>@endif
    </div>
@endif
