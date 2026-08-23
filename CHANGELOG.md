# Changelog

## [1.1.0] - 2026-08-23

Dark mode and multilingual support.

### Added
- **Dark mode.** The components now ship their own stylesheet
  (`resources/dist/rating-filament.css`), registered with Filament via
  `FilamentAsset`. Empty stars darken under Filament's `dark` class instead of
  staying `gray-300`. **Run `php artisan filament:assets` after upgrading.**
- **Translations.** English and Arabic ship in the box, publishable with
  `php artisan vendor:publish --tag=rating-filament-translations`. Every label,
  helper text and screen-reader string now resolves through the translator.
- `SECURITY.md` and a Dependabot config for the GitHub Actions ecosystem.

### Changed
- Inline styles moved out of the four Blade views into the stylesheet, which is
  what lets them respond to the theme at all. `starColor()` is unchanged and
  still accepts any CSS colour — it is applied as a custom property now, so a
  custom colour composes with the theme instead of overriding the whole
  declaration.
- Fill overlays use `inset-inline-start` rather than `left`, and half-star
  selection measures from the correct edge, so both fill the right way under
  `dir="rtl"`.
- Numeric values render through `Illuminate\Support\Number`, so the decimal
  separator follows the application locale. (`ext-intl` was already a hard
  requirement of `filament/support`; no new dependency.)
- `RatingsRelationManager::$title` replaced by `getTitle()`. A static property
  initialiser cannot call `__()`, so the title could never have been
  translated. **Override `getTitle()` instead of `$title`** if you were setting
  a custom one.
- GitHub Actions pinned to full commit SHAs.

## [1.0.1] - 2026-08-02

Maintenance release. **No functional changes** — the shipped code is identical
to 1.0.0, so there is no need to upgrade for behaviour reasons.

### Changed
- Repository moved to the `gaitco` organisation; README and cross-package
  links updated. The Composer package name is unchanged.
- README: added badges, setup context, and a note that `allowHalf()` renders
  half stars.

### Fixed
- CI matrix: two `include` rows differing only in `testbench` were silently
  merged by GitHub into one combination, dropping a leg and duplicating
  another. Rows now differ in more than one key, restoring full Laravel
  11/12/13 × Filament 4/5 coverage. (CI only — not shipped in the package.)

## [1.0.0] - 2026-08-02

### Added
- `RatingInput` form field with config-derived validation bounds
- `RatingColumn` table column bound to the `ratings_avg_rating` alias
- `RatingEntry` infolist entry
- `RatingsRelationManager` for moderating received ratings
- Publishable views under the `rating-filament-views` tag
