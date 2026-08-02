<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
        @include('rating-filament::components.stars', [
            'value' => (float) $getState(),
            'stars' => $getStars(),
            'starColor' => $getStarColor(),
        ])

        @if ($getShowValue())
            <span>{{ number_format((float) $getState(), 1) }}</span>
        @endif
    </div>
</x-dynamic-component>
