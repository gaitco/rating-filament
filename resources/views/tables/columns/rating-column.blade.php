<div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem;">
    @include('rating-filament::components.stars', [
        'value' => (float) $getState(),
        'stars' => $getStars(),
        'starColor' => $getStarColor(),
    ])

    @if ($getShowValue())
        <span>{{ number_format((float) $getState(), 1) }}</span>
    @endif

    @if ($getShowCount())
        <span style="opacity: 0.6;">({{ $record->ratings_count ?? '—' }})</span>
    @endif
</div>
