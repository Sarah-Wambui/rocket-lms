{{-- CVH Africa: start a new discussion (logged-in users). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
@endpush

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_community.header', [
            'title' => 'Start a discussion',
            'subtitle' => 'Ask a question or share something with the CVH community. Please be respectful and post in the right category.',
            'crumbs' => [['Community', '/community'], ['New discussion', null]],
        ])

        <div class="container">
            <form action="/community/topics" method="post" class="cvh-forum-box p-24 p-lg-32 mt-24">
                @csrf

                <div class="form-group">
                    <label class="font-14 text-dark font-weight-bold">Category</label>
                    <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                        <option value="">Choose a category</option>
                        @foreach($forumCategories as $category)
                            <option value="{{ $category->id }}" {{ (string)old('category_id', $selectedCategory) === (string)$category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="form-group mt-16">
                    <label class="font-14 text-dark font-weight-bold">Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="200" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. How do I reduce aflatoxin in stored maize?" required>
                    @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="form-group mt-16">
                    <label class="font-14 text-dark font-weight-bold">Your message</label>
                    <textarea name="body" rows="8" maxlength="10000" class="form-control @error('body') is-invalid @enderror" placeholder="Give as much detail as you can..." required>{{ old('body') }}</textarea>
                    @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-12 mt-24">
                    <button type="submit" class="btn btn-primary btn-lg">Post discussion</button>
                    <a href="/community" class="btn btn-outline-primary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </main>
@endsection
