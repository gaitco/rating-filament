<div class="gr-rating gr-rating--column">
    @include('rating-filament::components.stars', [
        'value' => (float) $getState(),
        'stars' => $getStars(),
        'starColor' => $getStarColor(),
    ])

    @if ($getShowValue())
        <span>{{ \Illuminate\Support\Number::format((float) $getState(), precision: 1) }}</span>
    @endif

    @if ($getShowCount())
        <span class="gr-rating__count">({{ $record->ratings_count ?? '—' }})</span>
    @endif
</div>
