# ghanem/rating-filament Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `ghanem/rating-filament`, a companion package giving Filament 4/5 panels a star input field, a sortable average-rating table column, an infolist star entry, and a ratings relation manager for `ghanem/rating`.

**Architecture:** Four Filament components, each a thin subclass of a Filament base class with a Blade view. All four share one star-rendering partial and one `HasStars` configuration concern. The table column deliberately binds to the `ratings_avg_rating` SQL alias produced by the core package's existing `withAvgRating()` scope, rather than to its per-row accessor, so display and sorting both resolve without N+1.

**Tech Stack:** PHP 8.2+, Laravel 11/12/13, Filament 4/5, Livewire 3, Alpine.js (bundled with Filament), Orchestra Testbench, **Pest**.

**Repository:** `/Users/abdullah/code/packages/rating-filament` — already `git init`-ed on branch `master`, containing only `docs/`. The sibling package `/Users/abdullah/code/packages/friendship-filament` is the reference for project shape; consult it whenever a convention is unclear. Its `tests/TestCase.php` in particular is a **known-working** Filament + Testbench harness.

## Global Constraints

- Package name: `ghanem/rating-filament`. Root namespace: `Ghanem\RatingFilament\`.
- `"php": "^8.2"`, `"illuminate/support": "^11.0|^12.0|^13.0"`, `"filament/filament": "^4.0|^5.0"`, `"ghanem/rating": "^2.1"`.
- `"minimum-stability": "stable"`, `"prefer-stable": true`.
- **`ghanem/rating` resolves from a local path repository during development.** `composer.json` declares:
  ```json
  "repositories": [
      { "type": "path", "url": "../rating", "options": { "symlink": true } }
  ]
  ```
  Composer only honours `repositories` in a *root* package, so consumers ignore it and it is safe to publish. `../rating` is tagged `v2.1.0`, which satisfies `^2.1`.
- **Tests use Pest** (`it('...', function () { ... })` with `expect()`), matching `ghanem/friendship-filament`. Run with `vendor/bin/pest`.
- Filament 3 is **not** supported. Never reference `Filament\Forms\Components\Component`; the v4/v5 base is `Filament\Schemas\Components\Component`.
- View namespace is `rating-filament`. All views are referenced as `rating-filament::...`.
- **No compiled assets.** No `FilamentAsset::register()`, no `package.json`, no build step. Filament already ships Alpine and Tailwind.
- Star colours are plain CSS colour strings (e.g. `#f59e0b`), **not** Filament colour names. This avoids coupling to Filament's internal colour system, which differs between v4 and v5.
- Configuration methods are `stars()`, `starColor()`, `showValue()`. Do **not** name a method `color()` — `Filament\Tables\Columns\Concerns\HasColor` already defines it and subclasses would collide.
- Every configuration setter accepts `Type | Closure` and is read back through Filament's `$this->evaluate()`.
- Every task ends on a green `vendor/bin/pest` and a commit.

---

## File Structure

| File | Responsibility |
|---|---|
| `composer.json` | Package metadata, constraints, path repo, autoload, auto-discovery |
| `src/RatingFilamentServiceProvider.php` | Registers the `rating-filament` view namespace; publishes views |
| `src/Concerns/HasStars.php` | Shared `stars()` / `starColor()` / `showValue()` config for all three display components |
| `resources/views/components/stars.blade.php` | The single star-rendering partial (CSS clip, no JS) |
| `src/Infolists/Components/RatingEntry.php` | Read-only stars for infolists |
| `resources/views/infolists/components/rating-entry.blade.php` | Entry view |
| `src/Tables/Columns/RatingColumn.php` | Sortable average-rating column |
| `resources/views/tables/columns/rating-column.blade.php` | Column view |
| `src/Forms/Components/RatingInput.php` | Interactive star picker |
| `resources/views/forms/components/rating-input.blade.php` | Input view (the only Alpine in the package) |
| `src/Resources/RelationManagers/RatingsRelationManager.php` | Abstract moderation relation manager |
| `tests/Pest.php` | Binds `TestCase` to every test in `tests/` |
| `tests/TestCase.php` | Testbench harness: Filament providers, default panel, sqlite, schema |
| `tests/Models/Post.php`, `tests/Models/User.php` | Ratable and author fixtures |
| `tests/Fixtures/FormComponent.php` | Livewire component hosting a schema, for field tests |

---

## Task 1: Scaffold the package

**Files:**
- Create: `composer.json`, `.gitignore`, `.gitattributes`, `LICENSE`
- Create: `src/RatingFilamentServiceProvider.php`
- Create: `resources/views/components/stars.blade.php` (placeholder, filled in Task 2)
- Create: `tests/Pest.php`, `tests/TestCase.php`, `tests/Models/Post.php`, `tests/Models/User.php`
- Test: `tests/Feature/InstallationTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Ghanem\RatingFilament\RatingFilamentServiceProvider`; `Ghanem\RatingFilament\Tests\TestCase`, bound to every test by `tests/Pest.php`, which creates the `ratings`, `posts` and `users` tables and boots a default Filament panel. Fixtures `Ghanem\RatingFilament\Tests\Models\Post` (uses `Ratingable`) and `...\Models\User` (uses `CanRate`).

- [ ] **Step 1: Create `composer.json`**

```json
{
    "name": "ghanem/rating-filament",
    "description": "Filament 4 & 5 components for the ghanem/rating package — star input, sortable average-rating column, infolist entry and review moderation.",
    "keywords": ["laravel", "filament", "rating", "stars", "review", "admin"],
    "license": "MIT",
    "authors": [
        {
            "name": "abdullah ghanem",
            "email": "3bdullah.ghanem@gmail.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "ghanem/rating": "^2.1",
        "filament/filament": "^4.0|^5.0",
        "illuminate/support": "^11.0|^12.0|^13.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0|^10.0|^11.0",
        "pestphp/pest": "^2.34|^3.0"
    },
    "repositories": [
        { "type": "path", "url": "../rating", "options": { "symlink": true } }
    ],
    "autoload": {
        "psr-4": {
            "Ghanem\\RatingFilament\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Ghanem\\RatingFilament\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Ghanem\\RatingFilament\\RatingFilamentServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "scripts": {
        "test": "vendor/bin/pest"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 2: Create `.gitignore`, `.gitattributes`, `LICENSE`**

`.gitignore`:

```
/vendor/
composer.lock
.phpunit.result.cache
.phpunit.cache/
.pest/
.DS_Store
```

`.gitattributes`:

```
* text=auto eol=lf

/.github        export-ignore
/tests          export-ignore
/docs           export-ignore
.gitattributes  export-ignore
.gitignore      export-ignore
phpunit.xml     export-ignore
```

Copy `LICENSE` from `../rating/LICENSE`.

`composer.lock` is gitignored on purpose: a library that commits its lock makes CI test one dependency set instead of the whole matrix.

- [ ] **Step 3: Run `composer install`**

Run: `composer install`
Expected: succeeds, and `ghanem/rating` resolves to the local path repo. Verify with
`composer show ghanem/rating` — the version should be `v2.1.0` or `dev-master`, and
the install path should be a symlink into `../rating`.

If it fails to resolve, check that `../rating` exists relative to this package and
that it has a `v2.1.0` tag (`git -C ../rating tag --points-at HEAD`).

- [ ] **Step 4: Write the failing test**

`tests/Feature/InstallationTest.php`:

```php
<?php

use Illuminate\Support\Facades\View;

it('registers the package view namespace', function () {
    expect(View::exists('rating-filament::components.stars'))->toBeTrue();
});

it('loads the core rating package', function () {
    expect(trait_exists(Ghanem\Rating\Traits\Ratingable::class))->toBeTrue()
        ->and(trait_exists(Ghanem\Rating\Traits\CanRate::class))->toBeTrue();
});
```

- [ ] **Step 5: Create the test harness**

`tests/Pest.php`:

```php
<?php

uses(Ghanem\RatingFilament\Tests\TestCase::class)->in(__DIR__);
```

`tests/TestCase.php` — the provider list and the default panel are copied from the
known-working `../friendship-filament/tests/TestCase.php`. Filament components
resolve against a *current panel*, so the `TestPanelProvider` marked `->default()`
is required; without it, component tests fail at boot.

```php
<?php

namespace Ghanem\RatingFilament\Tests;

use Filament\Panel;
use Filament\PanelProvider;
use Ghanem\Rating\RatingServiceProvider;
use Ghanem\RatingFilament\RatingFilamentServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin');
    }
}

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Filament\FilamentServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Livewire\LivewireServiceProvider::class,
            \BladeUI\Heroicons\BladeHeroiconsServiceProvider::class,
            \BladeUI\Icons\BladeIconsServiceProvider::class,
            RatingServiceProvider::class,
            RatingFilamentServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->integer('rating');
            $table->text('body')->nullable();
            $table->string('type')->nullable()->index();
            $table->float('weight')->nullable();
            $table->morphs('ratingable');
            $table->morphs('author');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}
```

`tests/Models/Post.php`:

```php
<?php

namespace Ghanem\RatingFilament\Tests\Models;

use Ghanem\Rating\Traits\Ratingable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Ratingable;

    protected $fillable = ['title'];
}
```

`tests/Models/User.php`:

```php
<?php

namespace Ghanem\RatingFilament\Tests\Models;

use Ghanem\Rating\Traits\CanRate;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use CanRate;

    protected $fillable = ['name'];
}
```

- [ ] **Step 6: Run the test to verify it fails**

Run: `vendor/bin/pest --filter="registers the package view namespace"`
Expected: FAIL — `RatingFilamentServiceProvider` does not exist.

- [ ] **Step 7: Write the service provider and the placeholder view**

`src/RatingFilamentServiceProvider.php`:

```php
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
```

`resources/views/components/stars.blade.php` — placeholder, replaced in Task 2:

```blade
{{-- Replaced in Task 2 --}}
<span></span>
```

- [ ] **Step 8: Run the tests to verify they pass**

Run: `vendor/bin/pest`
Expected: PASS, 2 tests.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "Scaffold package, service provider and Pest harness"
```

---

## Task 2: Star partial, HasStars concern, and RatingEntry

**Files:**
- Create: `src/Concerns/HasStars.php`
- Modify: `resources/views/components/stars.blade.php` (replace the placeholder)
- Create: `src/Infolists/Components/RatingEntry.php`
- Create: `resources/views/infolists/components/rating-entry.blade.php`
- Test: `tests/Feature/RatingEntryTest.php`

**Interfaces:**
- Consumes: `Ghanem\RatingFilament\Tests\TestCase` (bound automatically by `tests/Pest.php`).
- Produces:
  - `Ghanem\RatingFilament\Concerns\HasStars` with public methods
    `stars(int|Closure $stars): static`, `getStars(): int`,
    `starColor(string|Closure $color): static`, `getStarColor(): string`,
    `showValue(bool|Closure $condition = true): static`, `getShowValue(): bool`.
  - `Ghanem\RatingFilament\Infolists\Components\RatingEntry`.
  - The partial `rating-filament::components.stars`, which expects exactly these
    variables: `$value` (float), `$stars` (int), `$starColor` (string CSS colour).
    Tasks 3 and 4 include this same partial with the same three variables.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/RatingEntryTest.php`:

```php
<?php

use Ghanem\RatingFilament\Infolists\Components\RatingEntry;

it('defaults to five stars', function () {
    expect(RatingEntry::make('rating')->getStars())->toBe(5);
});

it('reads the star count from the core package config', function () {
    config()->set('rating.max', 10);

    expect(RatingEntry::make('rating')->getStars())->toBe(10);
});

it('prefers an explicit star count over config', function () {
    config()->set('rating.max', 10);

    expect(RatingEntry::make('rating')->stars(3)->getStars())->toBe(3);
});

it('accepts a closure for the star count', function () {
    expect(RatingEntry::make('rating')->stars(fn (): int => 7)->getStars())->toBe(7);
});

it('has a default star colour that can be overridden', function () {
    expect(RatingEntry::make('rating')->getStarColor())->toBe('#f59e0b')
        ->and(RatingEntry::make('rating')->starColor('#dc2626')->getStarColor())->toBe('#dc2626');
});

it('shows the numeric value by default and can hide it', function () {
    expect(RatingEntry::make('rating')->getShowValue())->toBeTrue()
        ->and(RatingEntry::make('rating')->showValue(false)->getShowValue())->toBeFalse();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `vendor/bin/pest --filter=RatingEntry`
Expected: FAIL — `RatingEntry` does not exist.

- [ ] **Step 3: Write the `HasStars` concern**

`src/Concerns/HasStars.php`:

```php
<?php

namespace Ghanem\RatingFilament\Concerns;

use Closure;

trait HasStars
{
    protected int | Closure | null $stars = null;

    protected string | Closure $starColor = '#f59e0b';

    protected bool | Closure $showValue = true;

    public function stars(int | Closure $stars): static
    {
        $this->stars = $stars;

        return $this;
    }

    public function getStars(): int
    {
        $stars = $this->evaluate($this->stars);

        if ($stars !== null) {
            return (int) $stars;
        }

        // Fall back to the core package's configured maximum so the widget and
        // the model layer always agree on the scale.
        return (int) (config('rating.max') ?? 5);
    }

    public function starColor(string | Closure $color): static
    {
        $this->starColor = $color;

        return $this;
    }

    public function getStarColor(): string
    {
        return $this->evaluate($this->starColor);
    }

    public function showValue(bool | Closure $condition = true): static
    {
        $this->showValue = $condition;

        return $this;
    }

    public function getShowValue(): bool
    {
        return (bool) $this->evaluate($this->showValue);
    }
}
```

- [ ] **Step 4: Write the shared star partial**

Replace `resources/views/components/stars.blade.php` entirely:

```blade
@php
    $max = max(1, (int) $stars);
    $percent = max(0, min(100, ((float) $value / $max) * 100));
    $glyphs = str_repeat('★', $max);
@endphp

<span
    role="img"
    aria-label="{{ number_format((float) $value, 1) }} out of {{ $max }}"
    style="position: relative; display: inline-block; white-space: nowrap; color: #d1d5db; letter-spacing: 1px;"
>
    {{ $glyphs }}

    <span
        aria-hidden="true"
        style="position: absolute; top: 0; left: 0; width: {{ $percent }}%; overflow: hidden; color: {{ $starColor }}; letter-spacing: 1px;"
    >{{ $glyphs }}</span>
</span>
```

Styles are inline rather than classed because the package ships no stylesheet and
must not depend on the host panel's Tailwind config. Publishing the views
(`--tag=rating-filament-views`) is the supported way to restyle.

- [ ] **Step 5: Write `RatingEntry` and its view**

`src/Infolists/Components/RatingEntry.php`:

```php
<?php

namespace Ghanem\RatingFilament\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Ghanem\RatingFilament\Concerns\HasStars;

class RatingEntry extends Entry
{
    use HasStars;

    protected string $view = 'rating-filament::infolists.components.rating-entry';
}
```

`resources/views/infolists/components/rating-entry.blade.php`:

```blade
<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
        @include('rating-filament::components.stars', [
            'value' => (float) $getState(),
            'stars' => $getStars(),
            'starColor' => $getStarColor(),
        ])

        @if ($getShowValue())
            <span>{{ number_format((float) $getState(), 1) }}</span>
        @endif
    </div>
</x-dynamic-component>
```

- [ ] **Step 6: Add a partial render test**

Append to `tests/Feature/RatingEntryTest.php`:

```php
it('clips the filled stars to the right width', function () {
    $html = view('rating-filament::components.stars', [
        'value' => 3.5,
        'stars' => 5,
        'starColor' => '#f59e0b',
    ])->render();

    expect($html)->toContain('width: 70%')
        ->and($html)->toContain('3.5 out of 5');
});

it('never clips beyond the full width', function () {
    $html = view('rating-filament::components.stars', [
        'value' => 99,
        'stars' => 5,
        'starColor' => '#f59e0b',
    ])->render();

    expect($html)->toContain('width: 100%');
});
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `vendor/bin/pest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add star partial, HasStars concern and RatingEntry"
```

---

## Task 3: RatingColumn

**Files:**
- Create: `src/Tables/Columns/RatingColumn.php`
- Create: `resources/views/tables/columns/rating-column.blade.php`
- Test: `tests/Feature/RatingColumnTest.php`

**Interfaces:**
- Consumes: `HasStars` and the `rating-filament::components.stars` partial from Task 2.
- Produces: `Ghanem\RatingFilament\Tables\Columns\RatingColumn`, whose `make()`
  defaults the column name to `ratings_avg_rating`, plus
  `showCount(bool|Closure $condition = true): static` and `getShowCount(): bool`.

This is the task the whole design hangs on. `Ratingable` exposes
`getAvgRatingAttribute()`, which looks like the natural binding and is the wrong
one: it fires a fresh aggregate query for every row and cannot be sorted in SQL.
Binding to the `ratings_avg_rating` alias that `withAvgRating()` already selects
makes both display and sorting free.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/RatingColumnTest.php`:

```php
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
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `vendor/bin/pest --filter=RatingColumn`
Expected: FAIL — `RatingColumn` does not exist.

- [ ] **Step 3: Write `RatingColumn`**

`src/Tables/Columns/RatingColumn.php`:

```php
<?php

namespace Ghanem\RatingFilament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Ghanem\RatingFilament\Concerns\HasStars;
use Illuminate\Database\Eloquent\Builder;

class RatingColumn extends Column
{
    use HasStars;

    protected string $view = 'rating-filament::tables.columns.rating-column';

    protected bool | Closure $showCount = false;

    /**
     * Defaults to the alias selected by ghanem/rating's withAvgRating() scope,
     * so display reads an already-selected column and sorting happens in SQL.
     */
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'ratings_avg_rating');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: stays correct even if a resource forgets withAvgRating().
        $this->sortable(
            query: fn (Builder $query, string $direction): Builder => $query->orderByAvgRating($direction),
        );
    }

    public function showCount(bool | Closure $condition = true): static
    {
        $this->showCount = $condition;

        return $this;
    }

    public function getShowCount(): bool
    {
        return (bool) $this->evaluate($this->showCount);
    }
}
```

- [ ] **Step 4: Write the column view**

`resources/views/tables/columns/rating-column.blade.php`:

```blade
<div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem;">
    @include('rating-filament::components.stars', [
        'value' => (float) $getState(),
        'stars' => $getStars(),
        'starColor' => $getStarColor(),
    ])

    @if ($getShowValue())
        <span>{{ number_format((float) $getState(), 1) }}</span>
    @endif

    @if ($getShowCount())
        <span style="opacity: 0.6;">({{ $record->ratings_count ?? $record->countRatings() }})</span>
    @endif
</div>
```

`$record` is provided to column views by Filament. The `??` falls back to a query
only when `withCountRatings()` was not applied, so the fast path stays fast.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `vendor/bin/pest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add RatingColumn bound to the withAvgRating alias"
```

---

## Task 4: RatingInput

**Files:**
- Create: `src/Forms/Components/RatingInput.php`
- Create: `resources/views/forms/components/rating-input.blade.php`
- Create: `tests/Fixtures/FormComponent.php`, `tests/Fixtures/views/form.blade.php`
- Modify: `tests/TestCase.php` (register the test view namespace)
- Test: `tests/Feature/RatingInputTest.php`

**Interfaces:**
- Consumes: `HasStars` and the star partial from Task 2.
- Produces: `Ghanem\RatingFilament\Forms\Components\RatingInput` with
  `clearable(bool|Closure $condition = true): static`, `isClearable(): bool`,
  `allowHalf(bool|Closure $condition = true): static`, `allowsHalf(): bool`.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/RatingInputTest.php`:

```php
<?php

use Ghanem\RatingFilament\Forms\Components\RatingInput;

it('defaults to five stars', function () {
    expect(RatingInput::make('rating')->getStars())->toBe(5);
});

it('is clearable by default and can be turned off', function () {
    expect(RatingInput::make('rating')->isClearable())->toBeTrue()
        ->and(RatingInput::make('rating')->clearable(false)->isClearable())->toBeFalse();
});

it('has half stars off by default', function () {
    expect(RatingInput::make('rating')->allowsHalf())->toBeFalse()
        ->and(RatingInput::make('rating')->allowHalf()->allowsHalf())->toBeTrue();
});

it('always attaches a numeric rule', function () {
    expect(RatingInput::make('rating')->getValidationRules())->toContain('numeric');
});

it('derives min and max rules from the core config', function () {
    config()->set('rating.min', 1);
    config()->set('rating.max', 5);

    $rules = RatingInput::make('rating')->getValidationRules();

    expect($rules)->toContain('min:1')
        ->and($rules)->toContain('max:5');
});

it('attaches no bounds when the config has none', function () {
    config()->set('rating.min', null);
    config()->set('rating.max', null);

    $rules = array_filter(
        RatingInput::make('rating')->getValidationRules(),
        fn ($rule) => is_string($rule),
    );

    expect($rules)->not->toContain('min:1')
        ->and($rules)->not->toContain('max:5');
});
```

If `getValidationRules()` is not the accessor on the installed Filament version,
inspect `vendor/filament/forms/src/Components/Field.php` and
`vendor/filament/schemas/src/Components/Concerns/HasValidationRules.php` for the
real name and adjust these three tests. The behaviour under test does not change:
bounds come from `config('rating.*')`.

- [ ] **Step 2: Run the tests to verify they fail**

Run: `vendor/bin/pest --filter=RatingInput`
Expected: FAIL — `RatingInput` does not exist.

- [ ] **Step 3: Write `RatingInput`**

`src/Forms/Components/RatingInput.php`:

```php
<?php

namespace Ghanem\RatingFilament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Ghanem\RatingFilament\Concerns\HasStars;

class RatingInput extends Field
{
    use HasStars;

    protected string $view = 'rating-filament::forms.components.rating-input';

    protected bool | Closure $isClearable = true;

    protected bool | Closure $allowHalf = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule('numeric');

        // Mirror the core package's bounds so the form rejects out-of-range
        // values with a field error instead of letting Rating::validateRating()
        // throw InvalidRatingException, which surfaces as a 500 in a panel.
        $min = config('rating.min');
        if ($min !== null) {
            $this->rule('min:' . $min);
        }

        $max = config('rating.max');
        if ($max !== null) {
            $this->rule('max:' . $max);
        }
    }

    public function clearable(bool | Closure $condition = true): static
    {
        $this->isClearable = $condition;

        return $this;
    }

    public function isClearable(): bool
    {
        return (bool) $this->evaluate($this->isClearable);
    }

    public function allowHalf(bool | Closure $condition = true): static
    {
        $this->allowHalf = $condition;

        return $this;
    }

    public function allowsHalf(): bool
    {
        return (bool) $this->evaluate($this->allowHalf);
    }
}
```

- [ ] **Step 4: Write the input view**

`resources/views/forms/components/rating-input.blade.php`:

```blade
@php
    $statePath = $getStatePath();
    $max = max(1, $getStars());
    $color = $getStarColor();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            hover: null,
            max: {{ $max }},
            half: {{ $allowsHalf() ? 'true' : 'false' }},
            clearable: {{ $isClearable() ? 'true' : 'false' }},
            valueFor(index, event) {
                if (! this.half) return index

                const box = event.currentTarget.getBoundingClientRect()

                return (event.clientX - box.left) < (box.width / 2) ? index - 0.5 : index
            },
            select(index, event) {
                const value = this.valueFor(index, event)

                this.state = (this.clearable && this.state === value) ? null : value
            },
            filled(index) {
                const current = this.hover ?? this.state ?? 0

                return current >= index - 0.25
            },
        }"
        @mouseleave="hover = null"
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        style="display: inline-flex; gap: 0.125rem;"
    >
        <template x-for="index in max" :key="index">
            <button
                type="button"
                role="radio"
                :aria-checked="state === index"
                :aria-label="index + ' of ' + max"
                @click="select(index, $event)"
                @mousemove="hover = valueFor(index, $event)"
                @keydown.arrow-right.prevent="state = Math.min(max, (state ?? 0) + (half ? 0.5 : 1))"
                @keydown.arrow-left.prevent="state = Math.max(0, (state ?? 0) - (half ? 0.5 : 1))"
                style="background: none; border: 0; padding: 0 1px; cursor: pointer; font-size: 1.5rem; line-height: 1;"
                :style="filled(index) ? 'color: {{ $color }}' : 'color: #d1d5db'"
            >★</button>
        </template>
    </div>
</x-dynamic-component>
```

This is the only Alpine in the package. `$wire.$entangle()` is the documented way to
bind a custom field's state, and `role="radiogroup"` with arrow-key handlers keeps it
operable without a mouse.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `vendor/bin/pest --filter=RatingInput`
Expected: PASS.

- [ ] **Step 6: Add a Livewire state test**

`tests/Fixtures/FormComponent.php`:

```php
<?php

namespace Ghanem\RatingFilament\Tests\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Ghanem\RatingFilament\Forms\Components\RatingInput;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FormComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['rating' => null]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([RatingInput::make('rating')])
            ->statePath('data');
    }

    public function render(): View
    {
        return view('rating-filament-tests::form');
    }
}
```

`tests/Fixtures/views/form.blade.php`:

```blade
<div>{{ $this->form }}</div>
```

Add to `tests/TestCase.php` inside `defineEnvironment()`:

```php
$app['view']->addNamespace('rating-filament-tests', __DIR__ . '/Fixtures/views');
```

Append to `tests/Feature/RatingInputTest.php`:

```php
it('renders inside a form and holds state', function () {
    Livewire::test(Ghanem\RatingFilament\Tests\Fixtures\FormComponent::class)
        ->assertOk()
        ->set('data.rating', 4)
        ->assertSet('data.rating', 4);
});
```

with `use Livewire\Livewire;` added to the file's imports.

If `$this->form` is not the correct accessor on the installed Filament version,
check `../friendship-filament/tests/` for how it renders a schema, and follow that.

- [ ] **Step 7: Run the full suite and commit**

Run: `vendor/bin/pest`
Expected: PASS.

```bash
git add -A
git commit -m "Add RatingInput with config-derived validation bounds"
```

---

## Task 5: RatingsRelationManager

**Files:**
- Create: `src/Resources/RelationManagers/RatingsRelationManager.php`
- Test: `tests/Feature/RatingsRelationManagerTest.php`

**Interfaces:**
- Consumes: `RatingInput` (Task 4) and `RatingColumn` (Task 3).
- Produces: abstract class
  `Ghanem\RatingFilament\Resources\RelationManagers\RatingsRelationManager` with
  `protected static string $relationship = 'ratings';` and overridable
  `form(Schema $schema): Schema` / `table(Table $table): Table`.

Note the constraint recorded in the spec: `Ratingable::ratings()` and
`CanRate::ratings()` share a name, so a model cannot use both traits without an
`insteadof`. This relation manager always means **ratings received**.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/RatingsRelationManagerTest.php`:

```php
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
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `vendor/bin/pest --filter=RatingsRelationManager`
Expected: FAIL — `RatingsRelationManager` does not exist.

- [ ] **Step 3: Write the relation manager**

`src/Resources/RelationManagers/RatingsRelationManager.php`:

```php
<?php

namespace Ghanem\RatingFilament\Resources\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Ghanem\RatingFilament\Forms\Components\RatingInput;
use Ghanem\RatingFilament\Tables\Columns\RatingColumn;

/**
 * Moderates the ratings a record has *received*.
 *
 * Extend it and attach it to a resource:
 *
 *     class PostRatingsRelationManager extends RatingsRelationManager {}
 */
abstract class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static ?string $title = 'Ratings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            RatingInput::make('rating')->required(),
            Textarea::make('body')->label('Review')->rows(4),
            TextInput::make('type')->helperText('Optional aspect, e.g. "food"'),
            TextInput::make('weight')->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rating')
            ->columns([
                TextColumn::make('author_id')->label('Author')->sortable(),
                RatingColumn::make('rating')->showValue(),
                TextColumn::make('body')->label('Review')->limit(60)->wrap(),
                TextColumn::make('type')->badge()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
```

There is intentionally no create action. A rating needs an author, and the right
author for an admin-created rating is application-specific — subclasses that want
one override `headerActions()`.

`RatingColumn::make('rating')` is passed an explicit name here because a relation
manager lists `Rating` records directly, where `rating` is a real column. The
`ratings_avg_rating` default only applies on a parent resource's table.

If any of `DeleteAction`, `EditAction`, `recordActions()` or `Schema` resolves to a
different namespace on the installed Filament version, check
`vendor/filament/actions/src/` and `vendor/filament/tables/src/Table.php`, then
correct the imports. Do not invent names.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add RatingsRelationManager for moderating received ratings"
```

---

## Task 6: README and CI

**Files:**
- Create: `README.md`, `CHANGELOG.md`
- Create: `.github/workflows/tests.yml`

**Interfaces:**
- Consumes: every component from Tasks 2–5.
- Produces: no code.

- [ ] **Step 1: Write the CI workflow**

Note the extra step removing the local path repository: CI has no `../rating`
checkout, so it must resolve `ghanem/rating` from Packagist.

`.github/workflows/tests.yml`:

```yaml
name: Tests

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        include:
          - { php: '8.2', filament: '^4.0', testbench: '^9.0' }
          - { php: '8.3', filament: '^4.0', testbench: '^10.0' }
          - { php: '8.3', filament: '^5.0', testbench: '^11.0' }
          - { php: '8.4', filament: '^5.0', testbench: '^11.0' }

    name: PHP ${{ matrix.php }} · Filament ${{ matrix.filament }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: sqlite3
          coverage: none

      - name: Install dependencies
        run: |
          composer config --unset repositories
          composer require "filament/filament:${{ matrix.filament }}" --no-interaction --no-update
          composer require --dev "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
          composer update --prefer-dist --prefer-stable --no-interaction

      - name: Run tests
        run: vendor/bin/pest
```

- [ ] **Step 2: Write the README**

`README.md`:

````markdown
# Laravel Rating for Filament

Filament 4 & 5 components for [`ghanem/rating`](https://github.com/gaitco/rating).

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
````

- [ ] **Step 3: Write the CHANGELOG**

`CHANGELOG.md`:

```markdown
# Changelog

## [1.0.0] - unreleased

### Added
- `RatingInput` form field with config-derived validation bounds
- `RatingColumn` table column bound to the `ratings_avg_rating` alias
- `RatingEntry` infolist entry
- `RatingsRelationManager` for moderating received ratings
- Publishable views under the `rating-filament-views` tag
```

- [ ] **Step 4: Run the full suite and commit**

Run: `vendor/bin/pest`
Expected: PASS.

```bash
git add -A
git commit -m "Add README, changelog and CI matrix"
```

---

## Self-Review

**Spec coverage:**

| Spec requirement | Task |
|---|---|
| Separate package, `^4.0\|^5.0`, PHP ^8.2 | 1 |
| `RatingInput` + `stars`/`allowHalf`/`starColor`/`clearable` | 4 |
| Validation passthrough from `config('rating.*')` | 4 |
| Alpine-only interaction, keyboard operable | 4 |
| `RatingColumn` defaulting to `ratings_avg_rating` | 3 |
| Default `sortable(query:)` via `orderByAvgRating()` | 3 |
| `showValue` / `showCount` | 2, 3 |
| `RatingEntry` | 2 |
| `RatingsRelationManager`, no create action | 5 |
| Shared Blade partial, no registered assets | 2 |
| Views publishable under `rating-filament-views` | 1 |
| `ratings()` collision documented | 5, 6 |
| N+1 test | 3 |

No gaps.

**Deviations from the spec, deliberate:**

1. The spec called the colour setter `color()`. Renamed to `starColor()` — Filament's
   `Filament\Tables\Columns\Concerns\HasColor` already defines `color()`, and a
   collision there would be a confusing runtime failure.
2. The spec described colours as Filament colour names (`'warning'`). Changed to raw
   CSS colour strings, because Filament's colour internals differ between v4 and v5
   and the whole point of targeting both majors is one codebase.
3. Tests use Pest rather than PHPUnit, matching the sibling `ghanem/friendship-filament`.
4. `ghanem/rating` is required at `^2.1` (not `^2.0`) and resolved from a local path
   repository during development. `^2.0` would have matched the mis-normalising `V2.01`
   tag, which points at pre-v2 code without `CanRate`, `type`, `weight` or the scopes
   this package depends on.

**Type consistency:** `getStars()`, `getStarColor()`, `getShowValue()`,
`getShowCount()`, `isClearable()`, `allowsHalf()` are defined once and used with the
same names in every view and test.

**Placeholder scan:** none.
