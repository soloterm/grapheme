# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2025-12-14

### Changed
- **3-6x performance improvement** for uncached lookups on many character types
- New byte-level fast paths for additional Unicode ranges:
  - 2-byte UTF-8 (C2-CB): Latin-1 Supplement, Latin Extended A/B, IPA, Spacing Modifiers
  - 3-byte UTF-8 (E2): Box Drawing, Block Elements, Arrows, Mathematical Operators
  - 3-byte UTF-8 (E3): Hiragana, Katakana, CJK Symbols
  - 3-byte UTF-8 (EA-ED): Korean Hangul Syllables
  - Zero-width characters: ZWSP, ZWJ, ZWNJ, BOM, Word Joiner
- Reduced redundant `ord()` calls by caching byte values

### Performance Benchmarks (uncached)
- Latin Extended: 897K → 4.5M ops/sec (5x faster)
- Korean Hangul: 482K → 3.1M ops/sec (6.5x faster)
- Hiragana/Katakana: 773K → 3.5M ops/sec (4.6x faster)
- Box Drawing: 775K → 3.5M ops/sec (4.5x faster)
- Mixed workload: 3.8M → 5.5M ops/sec (1.5x faster)

## [1.1.0] - 2025-11-26

### Added
- `clearCache()` method to manually clear the width cache for long-running processes
- `setMaxCacheSize()` method to configure maximum cache size (default: 10,000 entries)
- Automatic cache clearing when max size is exceeded to prevent memory leaks
- Empty string handling - `wcwidth('')` now returns 0

### Changed
- **2x performance improvement** for uncached lookups (~800k � ~1.6M calls/sec)
- Byte-level fast paths for common character types:
  - Single-byte ASCII: direct return without multibyte function calls
  - CJK characters: UTF-8 byte pattern detection (0xE4-0xE9 first byte)
  - Common emoji: UTF-8 byte pattern detection (0xF0 0x9F prefix)
- Cache size now tracked with counter instead of `count()` on every miss
- Improved error handling for `mb_strwidth()` edge cases

### Fixed
- PHP version in README now correctly states 8.1+ (was 8.2+)

## [1.0.0] - Initial Release

- Initial release with comprehensive Unicode width calculation
- Support for CJK, emoji, zero-width characters, combining marks, and more
- Smart caching for performance
- 170+ test assertions
