# Laravel Rating for Filament

Filament 4 & 5 components for [`ghanem/rating`](https://github.com/AbdullahGhanem/rating).

## Installation

```bash
composer require ghanem/rating-filament
```

## Table column

Show a sortable average rating on a resource's table. **The `modifyQueryUsing`
line is required** — it selects the `ratings_avg_rating` alias the column reads,
which is what keeps the table to one query instead of one per row.

```php
use Ghanem\RatingFilament\Tables\Columns\RatingColumn;

public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->withAvgRating()->withCountRatings())
        ->columns([
            TextColumn::make('title'),
            RatingColumn::make()->showCount(),
        ]);
}
```

Scoping to a rating type happens in the query, not on the column:

```php
->modifyQueryUsing(fn (Builder $query) => $query->withAvgRating('food'))
```

## Form field

```php
use Ghanem\RatingFilament\Forms\Components\RatingInput;

RatingInput::make('rating')
    ->stars(5)
    ->allowHalf()
    ->starColor('#f59e0b')
    ->required();
```

The star count defaults to `config('rating.max')`, and the field attaches
`min`/`max` validation rules from the same config so an out-of-range value
produces a field error instead of an `InvalidRatingException`.

`allowHalf()` lets the field store half-point values (`2.5`, `3.5`, ...); it
does not change the rendering. The widget currently fills whole stars only, so
a stored `2.5` looks identical to `2.0` until half-star rendering is added.

## Infolist entry

```php
use Ghanem\RatingFilament\Infolists\Components\RatingEntry;

RatingEntry::make('rating')->showValue();
```

## Relation manager

```php
use Ghanem\RatingFilament\Resources\RelationManagers\RatingsRelationManager;

class PostRatingsRelationManager extends RatingsRelationManager {}
```

Then register it on the resource:

```php
public static function getRelations(): array
{
    return [PostRatingsRelationManager::class];
}
```

It lists the ratings a record has **received**, with edit and delete actions for
moderation. There is no create action by default, because the correct author for
an admin-created rating is application-specific.

## Customising the markup

```bash
php artisan vendor:publish --tag=rating-filament-views
```

## Known limitation

`Ratingable::ratings()` and `CanRate::ratings()` share a method name, so a model
cannot use both traits without resolving the collision with `insteadof`. This
relation manager always means *ratings received*.
