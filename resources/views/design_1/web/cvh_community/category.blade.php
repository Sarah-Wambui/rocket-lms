{{-- CVH Africa: community forum category (list of discussions). --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
@endpush

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_community.header', [
            'title' => $category->title,
            'subtitle' => $category->description,
            'crumbs' => [['Community', '/community'], [$category->title, null]],
            'action' => '<a href="/community/new?category=' . $category->id . '" class="btn btn-primary btn-lg">Start a discussion</a>',
        ])

        <div class="container">
            <form action="/community/{{ $category->slug }}" method="get" class="d-flex gap-12 mt-24">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search discussions in {{ $category->title }}" class="form-control">
                <button type="submit" class="btn btn-primary btn-lg">Search</button>
            </form>

            <div class="cvh-forum-box mt-24">
                @forelse($topics as $topic)
                    @include('design_1.web.cvh_community.topic_row', ['topic' => $topic, 'users' => $users, 'showCategory' => false])
                @empty
                    <div class="p-24 text-center text-gray-500">
                        @if($search !== '')
                            No discussions match "{{ $search }}". <a href="/community/{{ $category->slug }}" class="text-primary">Show all</a>
                        @else
                            No discussions here yet. <a href="/community/new?category={{ $category->id }}" class="text-primary">Start the first one</a>.
                        @endif
                    </div>
                @endforelse
            </div>

            @if($topics->hasPages())
                <div class="mt-24">{{ $topics->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </main>
@endsection
