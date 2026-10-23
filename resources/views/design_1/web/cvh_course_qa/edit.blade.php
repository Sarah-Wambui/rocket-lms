{{-- CVH Africa: course Q&A — edit your own question or answer --}}
@extends("design_1.web.layouts.app")

@push("styles_top")
    <link rel="stylesheet" href="{{ getDesign1StylePath("products_lists") }}">
    @include('design_1.web.cvh_community.styles')
@endpush

@section("content")
    <main class="pb-120">
        @include('design_1.web.cvh_course_qa.header', [
            'title' => $type === 'question' ? 'Edit your question' : 'Edit your answer',
            'crumbs' => [[\Illuminate\Support\Str::limit($question->title, 40), "/course/{$course->slug}/questions/{$question->id}"], ['Edit', null]],
        ])

        <div class="container">
            <form action="/course/{{ $course->slug }}/qa/{{ $type }}/{{ $post->id }}/update" method="post" class="cvh-forum-box p-24 mt-24">
                @csrf
                @if($type === 'question')
                    <label class="font-14 font-weight-bold text-dark">Title</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" maxlength="200" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif
                <label class="font-14 font-weight-bold text-dark mt-12">{{ $type === 'question' ? 'Details' : 'Answer' }}</label>
                <textarea name="body" rows="8" maxlength="10000" class="form-control @error('body') is-invalid @enderror" required>{{ old('body', $post->body) }}</textarea>
                @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="d-flex gap-8 mt-16">
                    <button type="submit" class="btn btn-primary btn-lg">Save</button>
                    <a href="/course/{{ $course->slug }}/questions/{{ $question->id }}" class="btn btn-outline-primary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </main>
@endsection
