<a href="/innovations/{{ $innovator->slug }}" class="cvh-innov-card d-flex flex-column h-100 text-decoration-none">
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
        <h3 class="font-16 text-dark">{{ $innovator->business }}</h3>
        <div class="font-14 text-gray-500 mt-4">{{ $innovator->name }}</div>

        <div class="d-flex align-items-center gap-4 font-12 text-gray-500 mt-8">
            <x-iconsax-lin-location width="14px" height="14px"/>
            <span>{{ $innovator->location }}</span>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-auto pt-16">
            <span class="font-14 font-weight-bold text-primary">View profile</span>
            <x-iconsax-lin-arrow-right class="text-primary" width="18px" height="18px"/>
        </div>
    </div>
</a>
