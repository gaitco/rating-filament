<?php

use Ghanem\RatingFilament\Forms\Components\RatingInput;
use Livewire\Livewire;

it('defaults to five stars', function () {
    expect(RatingInput::make('rating')->getStars())->toBe(5);
});

it('is clearable by default and can be turned off', function () {
    expect(RatingInput::make('rating')->isClearable())->toBeTrue()
        ->and(RatingInput::make('rating')->clearable(false)->isClearable())->toBeFalse();
});

it('has half stars off by default', function () {
    expect(RatingInput::make('rating')->allowsHalf())->toBeFalse()
        ->and(RatingInput::make('rating')->allowHalf()->allowsHalf())->toBeTrue();
});

it('always attaches a numeric rule', function () {
    expect(RatingInput::make('rating')->getValidationRules())->toContain('numeric');
});

it('derives min and max rules from the core config', function () {
    config()->set('rating.min', 1);
    config()->set('rating.max', 5);

    $rules = RatingInput::make('rating')->getValidationRules();

    expect($rules)->toContain('min:1')
        ->and($rules)->toContain('max:5');
});

it('attaches no bounds when the config has none', function () {
    config()->set('rating.min', null);
    config()->set('rating.max', null);

    $rules = array_filter(
        RatingInput::make('rating')->getValidationRules(),
        fn ($rule) => is_string($rule),
    );

    expect($rules)->not->toContain('min:1')
        ->and($rules)->not->toContain('max:5');
});

it('renders inside a form and holds state', function () {
    Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)
        ->assertOk()
        ->set('data.rating', 4)
        ->assertSet('data.rating', 4);
});

it('shows the numeric value by default and can hide it', function () {
    expect(RatingInput::make('rating')->getShowValue())->toBeTrue()
        ->and(RatingInput::make('rating')->showValue(false)->getShowValue())->toBeFalse();
});

it('renders the two-layer clip markup driven by fill(), not the old filled() predicate', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)->html();

    // This is the exact formula from the fix: c - index + 1, clamped to [0, 1].
    // A regression back to `current >= index - 0.25` (which paints 2.5 as 2
    // solid stars) would not contain this string.
    // The button markup lives inside a single <template x-for="index in max">
    // block, which Alpine clones client-side — so the static render contains
    // exactly one base glyph and one clipped overlay glyph, not one per star.
    expect($html)->toContain('Math.max(0, Math.min(1, c - index + 1)) * 100')
        ->and($html)->not->toContain('filled(index)')
        ->and(substr_count($html, '★'))->toBe(2)
        ->and($html)->toContain("entangle('data.rating')");
});

it('renders the numeric value span coercing state to a number before toFixed', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)->html();

    // Guards the latent crash: if Livewire hydrates `state` as a string
    // (e.g. a consuming app's model casts the rating column to `decimal:`
    // instead of `float`), `(state ?? 0).toFixed(1)` throws because
    // .toFixed doesn't exist on strings. Number(...) coerces first.
    expect($html)->toContain('x-text="Number(state ?? 0).toFixed(1)"')
        ->and($html)->not->toContain('x-text="(state ?? 0).toFixed(1)"');
});

it('clips the overlay through the stylesheet class and an object-form :style binding', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)->html();

    // The clip (position/overflow/inset-inline-start/colour) now lives in
    // resources/dist/rating-filament.css so it can respond to dark mode and
    // RTL, which an inline style attribute cannot do. Two things must hold:
    //
    // 1. The class is present — without it the overlay loses its clip and
    //    renders as a second star in normal flow.
    // 2. :style stays in object form. Alpine's string form calls
    //    el.setAttribute('style', ...), replacing the whole attribute; the
    //    object form merges via el.style.setProperty. The parent still carries
    //    an inline --gr-star-filled custom property, so the distinction keeps
    //    mattering as soon as anything static lands on the overlay again.
    expect($html)->toContain('class="gr-rating-input__fill"')
        ->and($html)->toContain(':style="{ width: fill(index) + \'%\' }"')
        ->and($html)->not->toContain(":style=\"'width: '");
});

it('carries the star colour as a custom property the stylesheet consumes', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)->html();

    // BC guard for the inline-style removal: ->starColor() must keep working.
    // It now overrides a variable instead of a declaration.
    expect($html)->toContain('style="--gr-star-filled: #f59e0b"');
});
