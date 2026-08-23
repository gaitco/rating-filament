# Plan — Dark mode ready + Multilingual support (+ Security 52 → 100)

Target repo: `ghanem/rating-filament` (listed on filamentphp.com as **gait-rating**).
Goal: turn the two ✗ badges on the plugin page into ✓, for real — then tick the boxes.

Decisions taken: CSS via `FilamentAsset::register`; locales `en` + `ar`; security checks in the
same release.

---

## Phase 0 — Why the badges are currently false

| Badge | Mechanical cause |
|---|---|
| Dark mode ready | All styling lives in inline `style="…"` attributes across the 4 Blade views. Filament toggles dark mode with a `dark` class on `<html>`; an inline style has no selector and cannot react to it. Empty stars are `#d1d5db` (gray-300) — near-white on a `gray-900` panel. |
| Multilingual support | No `lang/` dir, no `loadTranslationsFrom()`. 8 hardcoded English strings, one of them built in JavaScript. |

Constraint that shapes Phase 1: a plugin's Blade files are **not** scanned by the host app's
Tailwind build, and Filament ships a fixed precompiled stylesheet. Arbitrary utilities like
`dark:text-gray-600` would simply not exist at runtime. So the plugin ships its own CSS.

---

## Phase 1 — Dark mode

### 1.1 `resources/dist/rating-filament.css` (new, hand-written, no build step)

Plain CSS. Custom properties carry the theming; `.dark` re-points only what changes.
Logical properties (`inset-inline-start`) instead of `left`, so RTL works for free in Phase 2.

```css
.gr-stars {
    --gr-star-filled: #f59e0b;
    --gr-star-empty: #d1d5db;
    position: relative;
    display: inline-block;
    white-space: nowrap;
    color: var(--gr-star-empty);
    letter-spacing: 1px;
}
.dark .gr-stars { --gr-star-empty: #4b5563; }   /* gray-600 */

.gr-stars__fill {
    position: absolute;
    top: 0;
    inset-inline-start: 0;      /* was `left: 0` — flips under dir="rtl" */
    overflow: hidden;
    color: var(--gr-star-filled);
    letter-spacing: 1px;
}
/* … .gr-rating-input, .gr-rating-input__star, .gr-rating-count … */
```

### 1.2 Register it — `src/RatingFilamentServiceProvider.php`

```php
FilamentAsset::register([
    Css::make('rating-filament', __DIR__ . '/../resources/dist/rating-filament.css'),
], package: 'ghanem/rating-filament');
```

Must be inside `boot()` guarded so it also works in non-panel (standalone) usage.

### 1.3 Strip inline styles from the 4 Blade views

`components/stars.blade.php`, `forms/components/rating-input.blade.php`,
`infolists/components/rating-entry.blade.php`, `tables/columns/rating-column.blade.php`.

**The `starColor()` public API is preserved exactly** — the one remaining inline style is a
single custom-property assignment, which the stylesheet then consumes:

```blade
<span class="gr-stars" style="--gr-star-filled: {{ $starColor }}">
```

So `->starColor('#ef4444')` keeps working; it just now overrides a variable instead of a
declaration. No BC break, no deprecation needed.

`opacity: 0.6` on the review count → `.gr-rating-count` class (opacity is already
theme-agnostic; moving it is tidiness, not a fix).

### 1.4 README

New "Dark mode" section + the required install step:

```bash
php artisan filament:assets
```

Called out as **required after install and after every upgrade** — this is the one failure
mode of the chosen approach (forget it → unstyled stars, silently).

---

## Phase 2 — Multilingual

### 2.1 `resources/lang/{en,ar}/rating-filament.php` (new)

```php
return [
    'stars' => [
        'aria_label'      => ':value out of :max',
        'star_aria_label' => ':index of :max',
    ],
    'relation_manager' => ['title' => 'Ratings'],
    'fields' => [
        'author'      => 'Author',
        'review'      => 'Review',
        'type'        => 'Type',
        'type_helper' => 'Optional aspect, e.g. "food"',
        'weight'      => 'Weight',
    ],
];
```

### 2.2 Service provider

```php
$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'rating-filament');

$this->publishes([
    __DIR__ . '/../resources/lang' => lang_path('vendor/rating-filament'),
], 'rating-filament-translations');
```

### 2.3 Replace the hardcoded strings

- `stars.blade.php` — `aria-label` → `__('rating-filament::rating-filament.stars.aria_label', [...])`
- `rating-input.blade.php` — the JS-built label `index + ' of ' + max`. Translate the template
  server-side with a placeholder token, interpolate in Alpine:
  ```blade
  x-data="{ …, label(i) { return @js(__('…star_aria_label', ['index' => '__I__', 'max' => $max])).replace('__I__', i) } }"
  :aria-label="label(index)"
  ```
- `RatingsRelationManager` — `->label()`, `->helperText()` → `__()` calls.

### 2.4 ⚠️ The static-title trap

`protected static ?string $title = 'Ratings';` **cannot** be translated — PHP forbids a function
call in a property initialiser. Silently stays English forever. Fix: delete the property and
override the method Filament provides:

```php
public static function getTitle(Model $ownerRecord, string $pageClass): string
{
    return __('rating-filament::rating-filament.relation_manager.title');
}
```

### 2.5 Locale-aware numbers

`number_format((float) $value, 1)` hardcodes `.` as the decimal separator — wrong in `ar`, `de`,
`fr`. Laravel ≥11 (already the floor) ships `Illuminate\Support\Number`:

```php
Number::format($value, precision: 1)
```

Three call sites: `stars`, `rating-entry`, `rating-column`.

### 2.6 RTL

Already handled by `inset-inline-start` in Phase 1.3. `inline-flex` reverses on its own under
`dir="rtl"`. Verify the fill overlay fills from the right in the `ar` locale.

---

## Phase 3 — Security 52 → ~100

Three failing checks, three small files:

1. `.github/workflows/tests.yml` — pin `actions/checkout@v4` and `shivammathur/setup-php@v2` to
   full 40-char commit SHAs, each with a `# v4.3.0`-style trailing comment.
2. `.github/dependabot.yml` — `github-actions` ecosystem, weekly. Also the thing that keeps
   those SHA pins from rotting; the two checks are a pair.
3. `SECURITY.md` — supported versions table + a private reporting address.

---

## Phase 4 — Tests

Extend the existing Pest suite (`tests/Feature/`):

- `app()->setLocale('ar')` → relation-manager title and aria-labels come back Arabic.
- The shipped CSS file exists and contains a `.dark ` selector (guards against a future refactor
  quietly re-inlining styles and killing the badge).
- `->starColor('#ef4444')` still lands in the rendered output (BC guard for the custom-property
  swap).
- Existing 5 feature test files must stay green — they assert on rendered markup, so the
  inline-style removal will break some assertions; update them.

---

## Phase 5 — Release & badge flip

1. `CHANGELOG.md` → `1.1.0` (new features, no BC break).
2. Tag + push; Packagist picks it up.
3. On **filamentphp.com → Plugins → gait-rating → Edit**: tick **Dark mode ready** and
   **Multilingual support**. These are self-declared flags on the plugin listing form — Phases
   1–2 are what makes the declaration true.

---

## Files touched

```
new:  resources/dist/rating-filament.css
new:  resources/lang/en/rating-filament.php
new:  resources/lang/ar/rating-filament.php
new:  .github/dependabot.yml
new:  SECURITY.md
edit: src/RatingFilamentServiceProvider.php
edit: src/Resources/RelationManagers/RatingsRelationManager.php
edit: resources/views/components/stars.blade.php
edit: resources/views/forms/components/rating-input.blade.php
edit: resources/views/infolists/components/rating-entry.blade.php
edit: resources/views/tables/columns/rating-column.blade.php
edit: .github/workflows/tests.yml
edit: README.md, CHANGELOG.md
edit: tests/Feature/*.php  (markup assertions)
```

## Deliberately skipped

- **No Tailwind/Vite build.** ~40 lines of hand-written CSS with custom properties does the job;
  a build pipeline for one stylesheet is a `node_modules` and a CI step for nothing.
- **No `Filament\Contracts\Plugin` class.** This is a standalone plugin (field/column/entry) —
  it has no panel-level config to register. Add one only if panel-wide defaults are ever needed.
- **No `FilamentColor` integration** (`->color('warning')` instead of hex). Would be more
  idiomatic but breaks the existing `starColor()` string API. Revisit at 2.0.
- **No machine-translated extra locales.** `en` + `ar` are authored; the publish tag lets users
  add their own.
