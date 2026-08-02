<?php

namespace Ghanem\RatingFilament;

use Illuminate\Support\ServiceProvider;

class RatingFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rating-filament');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/rating-filament'),
            ], 'rating-filament-views');
        }
    }
}
