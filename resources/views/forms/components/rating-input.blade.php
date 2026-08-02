@php
    $statePath = $getStatePath();
    $max = max(1, $getStars());
    $color = $getStarColor();
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

                return (event.clientX - box.left) < (box.width / 2) ? index - 0.5 : index
            },
            select(index, event) {
                const value = this.valueFor(index, event)

                this.state = (this.clearable && this.state === value) ? null : value
            },
            filled(index) {
                const current = this.hover ?? this.state ?? 0

                return current >= index - 0.25
            },
        }"
        @mouseleave="hover = null"
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        style="display: inline-flex; gap: 0.125rem;"
    >
        <template x-for="index in max" :key="index">
            <button
                type="button"
                role="radio"
                :aria-checked="state === index"
                :aria-label="index + ' of ' + max"
                @click="select(index, $event)"
                @mousemove="hover = valueFor(index, $event)"
                @keydown.arrow-right.prevent="state = Math.min(max, (state ?? 0) + (half ? 0.5 : 1))"
                @keydown.arrow-left.prevent="state = Math.max(0, (state ?? 0) - (half ? 0.5 : 1))"
                style="background: none; border: 0; padding: 0 1px; cursor: pointer; font-size: 1.5rem; line-height: 1;"
                :style="filled(index) ? 'color: {{ $color }}' : 'color: #d1d5db'"
            >★</button>
        </template>
    </div>
</x-dynamic-component>
