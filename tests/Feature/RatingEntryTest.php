<?php

use Ghanem\RatingFilament\Infolists\Components\RatingEntry;
use Livewire\Livewire;

it('defaults to five stars', function () {
    expect(RatingEntry::make('rating')->getStars())->toBe(5);
});

it('reads the star count from the core package config', function () {
    config()->set('rating.max', 10);

    expect(RatingEntry::make('rating')->getStars())->toBe(10);
});

it('prefers an explicit star count over config', function () {
    config()->set('rating.max', 10);

    expect(RatingEntry::make('rating')->stars(3)->getStars())->toBe(3);
});

it('accepts a closure for the star count', function () {
    expect(RatingEntry::make('rating')->stars(fn (): int => 7)->getStars())->toBe(7);
});

it('has a default star colour that can be overridden', function () {
    expect(RatingEntry::make('rating')->getStarColor())->toBe('#f59e0b')
        ->and(RatingEntry::make('rating')->starColor('#dc2626')->getStarColor())->toBe('#dc2626');
});

it('shows the numeric value by default and can hide it', function () {
    expect(RatingEntry::make('rating')->getShowValue())->toBeTrue()
        ->and(RatingEntry::make('rating')->showValue(false)->getShowValue())->toBeFalse();
});

it('clips the filled stars to the right width', function () {
    $html = view('rating-filament::components.stars', [
        'value' => 3.5,
        'stars' => 5,
        'starColor' => '#f59e0b',
    ])->render();

    expect($html)->toContain('width: 70%')
        ->and($html)->toContain('3.5 out of 5');
});

it('never clips beyond the full width', function () {
    $html = view('rating-filament::components.stars', [
        'value' => 99,
        'stars' => 5,
        'starColor' => '#f59e0b',
    ])->render();

    expect($html)->toContain('width: 100%');
});

it('renders the entry view with the clipped stars and the numeric value', function () {
    $html = Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\InfolistComponent::class)->html();

    expect($html)->toContain('width: 70%')
        ->and($html)->toContain('3.5')
        ->and(substr_count($html, '★'))->toBe(10);
});
