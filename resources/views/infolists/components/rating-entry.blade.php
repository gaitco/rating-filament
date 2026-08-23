<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="gr-rating">
        @include('rating-filament::components.stars', [
            'value' => (float) $getState(),
            'stars' => $getStars(),
            'starColor' => $getStarColor(),
        ])

        @if ($getShowValue())
            <span>{{ \Illuminate\Support\Number::format((float) $getState(), precision: 1) }}</span>
        @endif
    </div>
</x-dynamic-component>
