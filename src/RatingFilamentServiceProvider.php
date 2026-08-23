<?php

namespace Ghanem\RatingFilament;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class RatingFilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rating-filament');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'rating-filament');

        // Filament does not compile plugin Blade files, so the components ship
        // their own stylesheet. `php artisan filament:assets` copies it into
        // public/css/ghanem/rating-filament and Filament links it on every page.
        FilamentAsset::register([
            Css::make('rating-filament', __DIR__ . '/../resources/dist/rating-filament.css'),
        ], package: 'ghanem/rating-filament');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/rating-filament'),
            ], 'rating-filament-views');

            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/rating-filament'),
            ], 'rating-filament-translations');
        }
    }
}
