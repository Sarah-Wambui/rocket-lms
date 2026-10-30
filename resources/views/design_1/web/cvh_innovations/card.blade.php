{{-- The innovator and their innovation together on the listing; nothing opens another page. A full story (from the Innovator of the Month posts) unfolds in place. --}}
<div class="cvh-innov-card d-flex flex-column h-100">
    <div class="cvh-innov-card__image">
        <img src="{{ $innovator->photo }}" alt="{{ $innovator->name }}" loading="lazy">
        <span class="cvh-innov-badge">Cohort {{ $innovator->cohort }}</span>
        @if($innovator->is_winner)
            <span class="cvh-innov-badge cvh-innov-badge--winner">
                <x-iconsax-bol-medal-star width="14px" height="14px"/> Winner
            </span>
        @endif
    </div>

    <div class="d-flex flex-column flex-grow-1 p-16">
        <h3 class="font-16 text-dark">{{ $innovator->name }}</h3>

        <div class="d-flex align-items-center gap-4 font-12 text-gray-500 mt-4">
            <x-iconsax-lin-location width="14px" height="14px"/>
            <span>{{ $innovator->location }}</span>
        </div>

        <div class="cvh-innov-card__innovation mt-12">
            <span class="d-block font-12 text-gray-500">Innovation</span>
            <span class="d-block font-14 font-weight-bold text-primary mt-2">{{ $innovator->business }}</span>
        </div>

        @if(!empty($innovator->profile_html))
            @if(!empty($innovator->summary))
                <p class="font-14 text-gray-500 mt-12">{{ $innovator->summary }}</p>
            @endif

            <details class="cvh-innov-card__story mt-12">
                <summary class="font-14 font-weight-bold text-primary">Read the full story</summary>
                <div class="cvh-innov-profile__content font-14 text-gray-500 mt-8">{!! $innovator->profile_html !!}</div>
            </details>
        @endif
    </div>
</div>
