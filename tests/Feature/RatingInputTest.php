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
