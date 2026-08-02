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

it('keeps the overlay span\'s static clip styles alongside an object-form :style binding', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)->html();

    // Alpine's string form of :style (`:style="'width: ' + fill(index) + '%'"`)
    // calls el.setAttribute('style', ...) on init, which REPLACES the whole
    // attribute — wiping the static position/overflow/color styles the
    // overlay depends on for its clip to work, and leaving a second grey
    // star rendered in normal flow. The object form (`:style="{ width: ... }"`)
    // merges via el.style.setProperty instead, preserving the static styles.
    // A regression back to the string form would still contain "fill(" (the
    // math is unchanged) but the static styles below and the object-form
    // literal would be gone from the overlay span's opening tag.
    expect($html)->toContain(
        'style="position: absolute; top: 0; left: 1px; overflow: hidden; color: #f59e0b;"'
        . "\n                        "
        . ':style="{ width: fill(index) + \'%\' }"'
    )->and($html)->not->toContain(":style=\"'width: '");
});
