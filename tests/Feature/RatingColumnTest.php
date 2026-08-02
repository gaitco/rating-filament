<?php

use Ghanem\Rating\Models\Rating;
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

it('sorts by its own column instead of throwing when the model has no orderByAvgRating scope', function () {
    $user = User::create(['name' => 'U']);
    $post = Post::create(['title' => 'A']);

    $post->rating(['rating' => 3], $user);
    $post->rating(['rating' => 5], $user);
    $post->rating(['rating' => 1], $user);

    // Rating's own builder has no orderByAvgRating scope — this is the
    // RatingsRelationManager case, where the column lists Rating records
    // directly rather than an aggregated parent.
    $query = RatingColumn::make('rating')->applySort(Rating::query(), 'asc');

    expect($query->pluck('rating')->all())->toBe([1.0, 3.0, 5.0]);
});

it('renders a dash instead of calling a Ratingable-only method when the record has no eager-loaded count', function () {
    $user = User::create(['name' => 'U']);
    $post = Post::create(['title' => 'A']);
    $rating = $post->rating(['rating' => 4], $user);

    // Rating (unlike Post) does not use the Ratingable trait, so it has no
    // countRatings() method. If the column's view ever falls back to calling
    // it, this record proves the bug: it would throw BadMethodCallException.
    expect(method_exists($rating, 'countRatings'))->toBeFalse();

    $html = view('rating-filament::tables.columns.rating-column', [
        'getState' => fn () => 4.0,
        'getStars' => fn () => 5,
        'getStarColor' => fn () => '#f59e0b',
        'getShowValue' => fn () => false,
        'getShowCount' => fn () => true,
        'record' => $rating,
    ])->render();

    expect($html)->toContain('(—)');
});

it('renders the eager-loaded count when present, without touching the model', function () {
    $post = Post::create(['title' => 'A']);
    $post->setAttribute('ratings_count', 3);

    $html = view('rating-filament::tables.columns.rating-column', [
        'getState' => fn () => 4.0,
        'getStars' => fn () => 5,
        'getStarColor' => fn () => '#f59e0b',
        'getShowValue' => fn () => false,
        'getShowCount' => fn () => true,
        'record' => $post,
    ])->render();

    expect($html)->toContain('(3)');
});

it('renders the configured number of stars', function () {
    $post = Post::create(['title' => 'A']);

    $html = view('rating-filament::tables.columns.rating-column', [
        'getState' => fn () => 2.0,
        'getStars' => fn () => 5,
        'getStarColor' => fn () => '#f59e0b',
        'getShowValue' => fn () => false,
        'getShowCount' => fn () => false,
        'record' => $post,
    ])->render();

    expect(substr_count($html, '★'))->toBe(10); // 5 base + 5 clipped overlay glyphs
});
