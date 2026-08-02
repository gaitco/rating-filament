@php
    $max = max(1, (int) $stars);
    $percent = max(0, min(100, ((float) $value / $max) * 100));
    $glyphs = str_repeat('★', $max);
@endphp

<span
    role="img"
    aria-label="{{ number_format((float) $value, 1) }} out of {{ $max }}"
    style="position: relative; display: inline-block; white-space: nowrap; color: #d1d5db; letter-spacing: 1px;"
>
    {{ $glyphs }}

    <span
        aria-hidden="true"
        style="position: absolute; top: 0; left: 0; width: {{ $percent }}%; overflow: hidden; color: {{ $starColor }}; letter-spacing: 1px;"
    >{{ $glyphs }}</span>
</span>

