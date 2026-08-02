# Filament support for `ghanem/rating`

**Date:** 2026-08-02
**Status:** Approved, not yet implemented

## Context

`ghanem/rating` provides polymorphic ratings and reviews for Eloquent models via two
traits: `Ratingable` (models that receive ratings) and `CanRate` (models that author
them). It has no UI layer. Applications built on Filament currently have to hand-roll
star inputs, aggregate columns, and review moderation screens.

This spec designs a companion package that supplies those four surfaces.

## Goals

- A clickable star input for Filament forms.
- A sortable average-rating table column that does not N+1.
- A read-only star entry for infolists.
- A relation manager base class for moderating a record's ratings and reviews.

## Non-goals

- Authoring ratings from a parent resource's form. `RatingInput` edits `Rating`
  records only; it never creates a rating as a side effect of saving a `Post`.
  This keeps `CanRate` entirely out of the package.
- A front-end (non-admin) star widget.
- Filament 3 support.
- Resolving the `ratings()` trait collision described under Known constraints.

## Package shape

A new repository, `ghanem/rating-filament`.

```json
{
  "name": "ghanem/rating-filament",
  "require": {
    "php": "^8.2",
    "ghanem/rating": "^2.0",
    "filament/filament": "^4.0|^5.0",
    "illuminate/support": "^11.0|^12.0|^13.0"
  }
}
```

### Why a separate package

`ghanem/rating` supports Laravel 8 through 13 and has no dependency beyond
`illuminate/*`. Filament 4/5 requires PHP ^8.2 and a much narrower Laravel range.
Merging them would either drag Filament into every consumer's install or force the
core package to carry a conditional-registration branch and a permanently split CI
matrix. A separate package keeps each one's constraints honest.

### Why Filament ^4 | ^5 in one codebase

Verified against the `4.x` and `5.x` branches: the three base classes this package
extends are structurally identical across both majors.

| Base class | Namespace (v4 and v5) |
|---|---|
| `Field` | `Filament\Forms\Components\Field` (extends `Filament\Schemas\Components\Component`) |
| `Column` | `Filament\Tables\Columns\Column` (extends `ViewComponent`) |
| `Entry` | `Filament\Infolists\Components\Entry` (extends `Component`) |
| `RelationManager` | `Filament\Resources\RelationManagers\RelationManager` |

Filament 3 is excluded because its `Field` extends
`Filament\Forms\Components\Component` and it has no `Filament\Schemas` package at
all. Supporting it would require split base classes or a separate `1.x` branch, for a
major that new projects no longer start on.

## Components

### 1. `RatingInput`

```php
namespace Ghanem\RatingFilament\Forms\Components;

class RatingInput extends \Filament\Forms\Components\Field
```

Bound to a `Rating` record's `rating` column. Renders a row of stars; clicking star
_n_ sets the state to _n_.

Public API:

| Method | Default | Purpose |
|---|---|---|
| `->stars(int\|Closure)` | `config('rating.max')` ?? `5` | How many stars to draw |
| `->allowHalf(bool\|Closure)` | `false` | Permit `.5` increments |
| `->starColor(string\|Closure)` | `'#f59e0b'` | CSS colour for filled stars |
| `->clearable(bool\|Closure)` | `true` | Clicking the active star resets to `null` |

State is an `int` or `float`, matching the `Rating` model's `'rating' => 'float'` cast.

A star picker's range is `0..max`, so `config('rating.allow_negative')` is not
represented in this field. Negative ratings remain reachable through the model layer;
they are simply not expressible by clicking stars. A panel that needs them should use
a plain `TextInput::make('rating')->numeric()` instead.

**Validation passthrough.** On `setUp()` the field reads `config('rating.min')` and
`config('rating.max')` and attaches matching
`numeric`/`min`/`max` rules. This matters because `Rating::validateRating()` throws
`InvalidRatingException` — an unhandled 500 in a Filament panel. Deriving the rules
from the same config means the form rejects the value first, with a field-level error.

**Interaction.** Alpine only, inlined in the Blade view: `x-data` holding the current
and hovered value, `@click` to set, `@mousemove`/`@mouseleave` for preview, plus
`$wire.$entangle()` binding through Filament's standard `$getStatePath()`. Keyboard: the
star row is a `radiogroup` with arrow-key navigation, so the field is operable
without a mouse.

### 2. `RatingColumn`

```php
namespace Ghanem\RatingFilament\Tables\Columns;

class RatingColumn extends \Filament\Tables\Columns\Column
```

Renders read-only stars plus the numeric average and, optionally, the rating count.

**The N+1 decision.** `Ratingable` exposes `getAvgRatingAttribute()`, which is the
obvious binding and the wrong one: it issues a fresh aggregate query per row and
cannot be sorted in SQL. Instead `RatingColumn::make()` defaults its name to
`ratings_avg_rating` — precisely the alias `scopeWithAvgRating()` produces. The
resource opts in once:

```php
->modifyQueryUsing(fn (Builder $query) => $query->withAvgRating())
```

Display then reads an already-selected attribute, and `->sortable()` sorts on the
alias in SQL. Both are free.

As a safety net the column sets a default sort closure delegating to the package's
own scope, so sorting stays correct even if a consumer forgets the aggregate:

```php
$this->sortable(query: fn (Builder $q, string $direction) => $q->orderByAvgRating($direction));
```

Public API:

| Method | Default | Purpose |
|---|---|---|
| `->stars(int\|Closure)` | `config('rating.max')` ?? `5` | Scale to draw against |
| `->showValue(bool\|Closure)` | `true` | Append the numeric average |
| `->showCount(bool\|Closure)` | `false` | Append `(n)`; requires `withCountRatings()` |
| `->type(?string\|Closure)` | `null` | Scope label only; the aggregate itself is chosen by the resource's query |
| `->starColor(string\|Closure)` | `'#f59e0b'` | CSS colour for filled stars |

`->type()` deliberately does not modify the query — a Filament column has no access
to the base query. It only affects the tooltip/label. Consumers scoping by type pass
the type to `withAvgRating($type)` in `modifyQueryUsing`. This is documented on the
method so the limitation is discoverable rather than surprising.

### 3. `RatingEntry`

```php
namespace Ghanem\RatingFilament\Infolists\Components;

class RatingEntry extends \Filament\Infolists\Components\Entry
```

Read-only stars for view pages. Same `->stars()`, `->showValue()`, `->starColor()` surface
as `RatingColumn`, rendering the same shared partial. No query concerns — an infolist
already has the record.

### 4. `RatingsRelationManager`

```php
namespace Ghanem\RatingFilament\Resources\RelationManagers;

abstract class RatingsRelationManager extends \Filament\Resources\RelationManagers\RelationManager
{
    protected static string $relationship = 'ratings';
}
```

Abstract; consumers extend it and attach it to a resource. Ships sensible defaults
that subclasses may override:

- **Table:** author (via the `author` morph), `RatingColumn` for the individual score,
  truncated `body`, `type` badge, `weight`, `created_at`. Filters on `type` and on
  positive/negative score.
- **Form:** `RatingInput::make('rating')`, `Textarea::make('body')`,
  `TextInput::make('type')`, `TextInput::make('weight')->numeric()`.
- **Actions:** edit and delete, for moderation. No create action by default — a rating
  needs an author, and the correct author for an admin-created rating is
  application-specific. Subclasses that want one override `->headerActions()`.

## Rendering

One Blade partial, `resources/views/components/stars.blade.php`, draws a star row from
`(value, max, starColor)`. `RatingColumn`, `RatingEntry` and the
read-only state of `RatingInput` all render it; only `RatingInput` layers Alpine on top.

Filament already bundles Alpine and Tailwind, and the partial uses Filament's own
colour tokens. **No `FilamentAsset::register()` call and no compiled CSS/JS ship with
this package** — a build step here would be ceremony with no payoff. If a future
feature genuinely needs bundled JS, that is when the asset pipeline gets added.

Views are registered with `loadViewsFrom(__DIR__.'/../resources/views', 'rating-filament')`
and published under the `rating-filament-views` tag.

## Known constraints

1. **`ratings()` collides across the two traits.** `Ratingable::ratings()` returns
   ratings *received*; `CanRate::ratings()` returns ratings *authored*. A model that
   uses both traits is a PHP fatal (trait method collision) unless the consumer
   resolves it with `insteadof`. `RatingsRelationManager` therefore assumes
   `$relationship = 'ratings'` means *ratings received*. Fixing the collision belongs
   in `ghanem/rating`, not here, and is out of scope for this spec.

2. **No aggregate write path.** There is deliberately no way to set an average from a
   parent resource form. Averages are derived; the only writable thing is an
   individual `Rating` row.

## File layout

```
src/
  RatingFilamentServiceProvider.php
  Forms/Components/RatingInput.php
  Tables/Columns/RatingColumn.php
  Infolists/Components/RatingEntry.php
  Resources/RelationManagers/RatingsRelationManager.php
  Concerns/HasStars.php              # shared stars()/starColor()/showValue() config
resources/views/
  components/stars.blade.php
  forms/components/rating-input.blade.php
  tables/columns/rating-column.blade.php
  infolists/components/rating-entry.blade.php
tests/
```

`Concerns\HasStars` exists because three classes need the identical
`stars()`/`starColor()`/`showValue()` configuration surface. It is extracted on
the third use, not speculatively.

## Testing

Testbench plus Filament's Livewire test helpers, mirroring the core package's setup.

| Test | Asserts |
|---|---|
| Field renders and sets state | Clicking star _n_ yields state `n` |
| Field validation passthrough | With `rating.max = 5`, submitting `9` fails validation rather than throwing `InvalidRatingException` |
| Field honours `config('rating.max')` | Star count derives from config when `->stars()` is not called |
| Column sorts in SQL | `assertCanSortTableByColumn`, and the sort is applied to the query, not in PHP |
| **Column does not N+1** | With `withAvgRating()` applied, rendering _n_ rows issues a constant number of queries — asserted via a `DB::listen` counter, not by eyeballing |
| Entry renders | Correct filled/empty star counts for a given value |
| RelationManager | Lists a record's received ratings; delete action removes one |

The N+1 test is the one that matters most: it is the assertion that pins the entire
column design in place, and it is the failure mode a reviewer cannot see by reading
the code.

## Rollout

1. Scaffold the repo and service provider.
2. Shared Blade partial + `HasStars`.
3. `RatingEntry` (simplest; proves the partial).
4. `RatingColumn` + the N+1 and sorting tests.
5. `RatingInput` + Alpine + validation passthrough.
6. `RatingsRelationManager`.
7. README with a copy-pasteable resource example, including the required
   `modifyQueryUsing` line.
