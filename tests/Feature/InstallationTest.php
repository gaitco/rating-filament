<?php

use Illuminate\Support\Facades\View;

it('registers the package view namespace', function () {
    expect(View::exists('rating-filament::components.stars'))->toBeTrue();
});

it('loads the core rating package', function () {
    expect(trait_exists(Ghanem\Rating\Traits\Ratingable::class))->toBeTrue()
        ->and(trait_exists(Ghanem\Rating\Traits\CanRate::class))->toBeTrue();
});
