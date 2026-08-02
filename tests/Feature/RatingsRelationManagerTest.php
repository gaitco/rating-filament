<?php

use Ghanem\RatingFilament\Resources\RelationManagers\RatingsRelationManager;
use Ghanem\RatingFilament\Tests\Models\Post;
use Ghanem\RatingFilament\Tests\Models\User;

it('is abstract so consumers must extend it', function () {
    expect((new ReflectionClass(RatingsRelationManager::class))->isAbstract())->toBeTrue();
});

it('targets the ratings relationship', function () {
    $property = (new ReflectionClass(RatingsRelationManager::class))->getProperty('relationship');
    $property->setAccessible(true);

    expect($property->getValue())->toBe('ratings');
});

it('scopes to the ratings a record received', function () {
    $author = User::create(['name' => 'A']);
    $mine = Post::create(['title' => 'Mine']);
    $other = Post::create(['title' => 'Other']);

    $mine->rating(['rating' => 5, 'body' => 'great'], $author);
    $other->rating(['rating' => 1, 'body' => 'bad'], $author);

    expect($mine->ratings()->count())->toBe(1)
        ->and($mine->ratings()->first()->body)->toBe('great');
});
