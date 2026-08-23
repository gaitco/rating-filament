@php
    $statePath = $getStatePath();
    $max = max(1, $getStars());
    $color = $getStarColor();

    // Translated server-side with a token standing in for the star number, so
    // Alpine only has to substitute the index rather than concatenate English.
    $starLabelTemplate = __('rating-filament::rating-filament.stars.star_aria_label', [
        'index' => '__INDEX__',
        'max' => $max,
    ]);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            hover: null,
            max: {{ $max }},
            half: {{ $allowsHalf() ? 'true' : 'false' }},
            clearable: {{ $isClearable() ? 'true' : 'false' }},
            valueFor(index, event) {
                if (! this.half) return index

                const box = event.currentTarget.getBoundingClientRect()
                const offset = getComputedStyle(event.currentTarget).direction === 'rtl'
                    ? box.right - event.clientX
                    : event.clientX - box.left

                return offset < (box.width / 2) ? index - 0.5 : index
            },
            select(index, event) {
                const value = this.valueFor(index, event)

                this.state = (this.clearable && this.state === value) ? null : value
            },
            fill(index) {
                const c = this.hover ?? this.state ?? 0

                return Math.max(0, Math.min(1, c - index + 1)) * 100
            },
            starLabel(index) {
                return @js($starLabelTemplate).replace('__INDEX__', index)
            },
        }"
        @mouseleave="hover = null"
        role="radiogroup"
        class="gr-rating-input"
        style="--gr-star-filled: {{ $color }}"
        aria-label="{{ $getLabel() }}"
    >
        <div class="gr-rating-input__stars">
            <template x-for="index in max" :key="index">
                <button
                    type="button"
                    role="radio"
                    class="gr-rating-input__star"
                    :aria-checked="state === index"
                    :aria-label="starLabel(index)"
                    @click="select(index, $event)"
                    @mousemove="hover = valueFor(index, $event)"
                    @keydown.arrow-right.prevent="state = Math.min(max, (state ?? 0) + (half ? 0.5 : 1))"
                    @keydown.arrow-left.prevent="state = Math.max(0, (state ?? 0) - (half ? 0.5 : 1))"
                >★<span
                        aria-hidden="true"
                        class="gr-rating-input__fill"
                        :style="{ width: fill(index) + '%' }"
                    >★</span></button>
            </template>
        </div>

        @if ($getShowValue())
            <span x-text="Number(state ?? 0).toFixed(1)"></span>
        @endif
    </div>
</x-dynamic-component>
