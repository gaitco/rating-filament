# Changelog

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
