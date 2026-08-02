<?php

use Filament\Schemas\Schema;
use Filament\Tables\Table;
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

it('builds a form and table without throwing', function () {
    $manager = new class extends RatingsRelationManager {};

    // Column::make()/Field::make() both run setUp() eagerly, so this exercises
    // the RatingInput and RatingColumn wiring (rules, sort closure) — not just
    // that form()/table() are syntactically valid.
    $schema = $manager->form(Schema::make());
    expect($schema)->toBeInstanceOf(Schema::class);

    $table = $manager->table(Table::make($manager));
    expect($table)->toBeInstanceOf(Table::class);
});
