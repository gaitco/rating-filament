<?php

use Filament\Support\Facades\FilamentAsset;

$css = fn (): string => file_get_contents(__DIR__ . '/../../resources/dist/rating-filament.css');

it('registers its stylesheet with Filament under the package name', function () {
    // getStyles() returns a flat list, not an id-keyed map.
    $style = collect(FilamentAsset::getStyles(['ghanem/rating-filament']))
        ->firstWhere(fn ($asset) => $asset->getId() === 'rating-filament');

    expect($style)->not->toBeNull()
        ->and($style->getPath())->toEndWith('resources/dist/rating-filament.css')
        ->and(file_exists($style->getPath()))->toBeTrue();
});

it('themes the empty stars for dark mode', function () use ($css) {
    // The whole point of the badge: an inline style attribute has no selector
    // and cannot react to the `dark` class Filament puts on <html>. If this
    // selector ever disappears, "Dark mode ready" on filamentphp.com is a lie.
    expect($css())->toContain('.dark .gr-stars')
        ->and($css())->toContain('--gr-star-empty');
});

it('anchors the fill overlays with logical properties so RTL fills from the right edge', function () use ($css) {
    // `left: 0` would fill 4.2/5 stars from the wrong side under dir="rtl".
    expect($css())->toContain('inset-inline-start')
        ->and($css())->not->toMatch('/^\s*left:/m');
});

it('keeps theme-dependent styling out of the Blade views', function () {
    // Regression guard: re-inlining any of these colours silently breaks dark
    // mode again while every other test still passes.
    $views = collect(glob(__DIR__ . '/../../resources/views/**/*.blade.php'))
        ->merge(glob(__DIR__ . '/../../resources/views/*/*/*.blade.php'))
        ->unique()
        ->map(fn (string $path): string => file_get_contents($path))
        ->implode("\n");

    expect($views)->not->toContain('#d1d5db')
        ->and($views)->not->toContain('position: absolute')
        ->and($views)->not->toContain('opacity: 0.6');
});
