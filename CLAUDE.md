# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `soloterm/grapheme`, a PHP library for calculating the display width of Unicode graphemes in terminal environments. It determines how many columns a character will occupy (0, 1, or 2) when rendered in a terminal.

## Commands

```bash
# Run tests
composer test
# or directly:
./vendor/bin/phpunit

# Run a single test
./vendor/bin/phpunit --filter "test_name"
```

## Architecture

The library consists of a single class: `SoloTerm\Grapheme\Grapheme`

### Key Method: `wcwidth(string $grapheme): int`

Returns the display width (0, 1, or 2) of a Unicode grapheme cluster. The method uses a priority-ordered decision tree:

1. **Cache lookup** - Returns immediately if already calculated
2. **Byte-level fast paths** - Single-byte ASCII, CJK (UTF-8 0xE4-0xE9), emoji (0xF0 0x9F) detected by raw bytes
3. **Zero-width detection** - ZWJ, ZWNJ, and other invisible characters
4. **Flag sequences** - Regional indicators and subdivision flags
5. **Complex scripts** - Devanagari and other scripts needing special handling
6. **Normalization** - NFC normalization only when combining marks detected
7. **ZWJ sequences** - Emoji family/profession sequences
8. **Variation selectors** - FE0E (text) vs FE0F (emoji) presentation
9. **Fallback** - `mb_strwidth()` with zero-width filtering

### Cache Management

- Results cached in static `$cache` array
- `$cacheSize` counter avoids `count()` on every miss
- Auto-clears when `$maxCacheSize` (default 10,000) exceeded
- `clearCache()` and `setMaxCacheSize()` available for long-running processes

### Unicode Patterns

The class defines ~15 static regex patterns for matching various Unicode ranges (emoji, CJK, zero-width chars, combining marks, etc.). These are compiled once and reused.

## Dependencies

- `symfony/polyfill-intl-normalizer` - For Unicode normalization
- `ext-intl` - Optional, recommended for better performance
