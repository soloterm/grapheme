# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2025-11-26

### Added
- `clearCache()` method to manually clear the width cache for long-running processes
- `setMaxCacheSize()` method to configure maximum cache size (default: 10,000 entries)
- Automatic cache clearing when max size is exceeded to prevent memory leaks
- Empty string handling - `wcwidth('')` now returns 0

### Changed
- **2x performance improvement** for uncached lookups (~800k ’ ~1.6M calls/sec)
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
