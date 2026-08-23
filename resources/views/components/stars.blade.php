@php
    $max = max(1, (int) $stars);
    $percent = max(0, min(100, ((float) $value / $max) * 100));
    $glyphs = str_repeat('★', $max);
@endphp

<span
    role="img"
    class="gr-stars"
    style="--gr-star-filled: {{ $starColor }}"
    aria-label="{{ __('rating-filament::rating-filament.stars.aria_label', [
        'value' => \Illuminate\Support\Number::format((float) $value, precision: 1),
        'max' => $max,
    ]) }}"
>
    {{ $glyphs }}

    <span
        aria-hidden="true"
        class="gr-stars__fill"
        style="width: {{ $percent }}%"
    >{{ $glyphs }}</span>
</span>
