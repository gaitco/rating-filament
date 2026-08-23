<?php

use Ghanem\RatingFilament\Tests\Models\Post;
use Illuminate\Support\Facades\View;

class LocalizedRatingsRelationManager extends Ghanem\RatingFilament\Resources\RelationManagers\RatingsRelationManager {}

it('registers the package translation namespace', function () {
    expect(__('rating-filament::rating-filament.relation_manager.title'))->toBe('Ratings');
});

it('translates the relation manager title', function () {
    app()->setLocale('ar');

    expect(LocalizedRatingsRelationManager::getTitle(new Post, ''))->toBe('التقييمات');
});

it('resolves the title through a method, not an untranslatable static property', function () {
    // A `protected static ?string $title` initialiser cannot call __(), so the
    // title would be frozen in English no matter the locale. Guard the shape.
    $property = new ReflectionClass(
        Ghanem\RatingFilament\Resources\RelationManagers\RatingsRelationManager::class,
    );

    expect($property->getStaticPropertyValue('title', null))->toBeNull();
});

it('translates the star aria-label', function () {
    app()->setLocale('ar');

    $html = View::make('rating-filament::components.stars', [
        'value' => 4.2,
        'stars' => 5,
        'starColor' => '#f59e0b',
    ])->render();

    expect($html)->toContain('4.2 من 5')
        ->and($html)->not->toContain('out of');
});

it('falls back to English for a locale it does not ship', function () {
    app()->setLocale('de');

    expect(__('rating-filament::rating-filament.fields.review'))->toBe('Review');
});

it('keeps every shipped locale in step with the English keys', function () {
    // Adding a key to en/ and forgetting ar/ ships a raw key like
    // "rating-filament::rating-filament.fields.foo" into the UI.
    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($items as $key => $value) {
            $keys = array_merge(
                $keys,
                is_array($value) ? $flatten($value, "{$prefix}{$key}.") : ["{$prefix}{$key}"],
            );
        }

        return $keys;
    };

    $english = $flatten(require __DIR__ . '/../../resources/lang/en/rating-filament.php');

    foreach (glob(__DIR__ . '/../../resources/lang/*/rating-filament.php') as $path) {
        expect($flatten(require $path))
            ->toEqualCanonicalizing($english, "Locale mismatch in {$path}");
    }
});

it('publishes its translations under a tag', function () {
    $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
        Ghanem\RatingFilament\RatingFilamentServiceProvider::class,
        'rating-filament-translations',
    );

    expect($paths)->not->toBeEmpty()
        ->and(array_values($paths)[0])->toContain('vendor/rating-filament');
});
