# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-03-16

### Added
- `clearCache()` to manually clear the width cache for long-running processes
- `setMaxCacheSize()` to configure the maximum cache size
- Automatic cache clearing when the cache limit is exceeded
- `Grapheme::split(string $text): array` for full-string grapheme segmentation
- `Grapheme::splitChunk(string $carry, string $chunk): array{graphemes: array, carry: string}` for streaming segmentation across arbitrary byte boundaries
- Deterministic fuzz tests for full-string and chunked segmentation behavior
- `benchmarks/wcwidth.php` and `composer benchmark` for standalone width benchmarking
- Coverage for Indic conjunct segmentation and chunked segmentation parity

### Changed
- Substantially improved uncached `wcwidth()` performance with additional byte-level fast paths and reduced redundant `ord()` calls
- Added fast paths for ASCII, CJK, emoji, Latin Extended ranges, box drawing, arrows, mathematical operators, and Hangul syllables
- Cache size is tracked with a counter instead of `count()` on every miss
- Segmentation now preserves grapheme clusters across chunk boundaries for combining marks, variation selectors, ZWJ sequences, emoji modifiers, and regional indicator flags
- Valid UTF-8 segmentation now prefers native grapheme boundaries when available and falls back to regex-based `\X` segmentation when the grapheme backend is unavailable
- Streaming segmentation now keeps incomplete trailing UTF-8 in `carry` and preserves invalid UTF-8 bytes as opaque segments
- Added required Symfony polyfills for grapheme and multibyte string support in consumer installs
- Moved benchmark timing out of the PHPUnit correctness suite and into a dedicated benchmark script
- Expanded documentation for segmentation, streaming flush behavior, invalid-byte handling, and native `intl` boundary differences

### Fixed
- `wcwidth('')` now returns `0`
- Improved memory-management edge cases around the width cache
- Improved `mb_strwidth()` fallback handling for edge cases
- README now correctly documents PHP 8.1+ support
- Standalone combining marks and standalone variation selectors now return width `0`
- Removed an over-broad width heuristic in the `E3` block that misclassified characters such as `U+303F`
- Flag sequence detection now uses explicit regional-indicator ranges instead of the non-portable `\p{Regional_Indicator}` property


## [1.0.2] - 2025-03-24

### Fixed
- Relaxed the `symfony/polyfill-intl-normalizer` constraint to a stable `^1.27.0`

## [1.0.1] - 2025-03-21

### Changed
- Broadened package support to PHP 8.1+

## [1.0.0] - Initial Release

- Initial release with comprehensive Unicode width calculation
- Support for CJK, emoji, zero-width characters, combining marks, and more
- Smart caching for performance
- 170+ test assertions

[Unreleased]: https://github.com/soloterm/grapheme/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/soloterm/grapheme/compare/v1.0.2...v1.1.0
[1.0.2]: https://github.com/soloterm/grapheme/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/soloterm/grapheme/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/soloterm/grapheme/releases/tag/v1.0.0
