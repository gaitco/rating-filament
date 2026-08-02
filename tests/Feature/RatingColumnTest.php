<?php

use Ghanem\RatingFilament\Tables\Columns\RatingColumn;
use Ghanem\RatingFilament\Tests\Models\Post;
use Ghanem\RatingFilament\Tests\Models\User;
use Illuminate\Support\Facades\DB;

it('defaults to the withAvgRating alias', function () {
    expect(RatingColumn::make()->getName())->toBe('ratings_avg_rating');
});

it('respects an explicit name', function () {
    expect(RatingColumn::make('score')->getName())->toBe('score');
});

it('is sortable by default', function () {
    expect(RatingColumn::make()->isSortable())->toBeTrue();
});

it('hides the count by default and can show it', function () {
    expect(RatingColumn::make()->getShowCount())->toBeFalse()
        ->and(RatingColumn::make()->showCount()->getShowCount())->toBeTrue();
});

it('reads the alias the core scope selects', function () {
    $post = Post::create(['title' => 'A']);
    $user = User::create(['name' => 'U']);
    $post->rating(['rating' => 4], $user);

    $loaded = Post::withAvgRating()->first();

    expect((float) $loaded->ratings_avg_rating)->toEqualWithDelta(4.0, 0.001);
});

it('does not query per row when reading the eager-loaded alias', function () {
    $user = User::create(['name' => 'U']);

    foreach (range(1, 10) as $i) {
        Post::create(['title' => "Post {$i}"])->rating(['rating' => 5], $user);
    }

    $posts = Post::withAvgRating()->get();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    foreach ($posts as $post) {
        $post->ratings_avg_rating;
    }

    expect($queries)->toBe(0);
});

it('documents the N+1 trap this column avoids', function () {
    $user = User::create(['name' => 'U']);

    foreach (range(1, 10) as $i) {
        Post::create(['title' => "Post {$i}"])->rating(['rating' => 5], $user);
    }

    $posts = Post::withAvgRating()->get();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    foreach ($posts as $post) {
        $post->avgRating();
    }

    // The accessor costs one query per row — this is why the column binds to
    // the alias instead.
    expect($queries)->toBe(10);
});
