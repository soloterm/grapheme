# Grapheme

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soloterm/grapheme)](https://packagist.org/packages/soloterm/grapheme)
[![Total Downloads](https://img.shields.io/packagist/dt/soloterm/grapheme)](https://packagist.org/packages/soloterm/grapheme)
[![License](https://img.shields.io/packagist/l/soloterm/grapheme)](https://packagist.org/packages/soloterm/grapheme)

A highly optimized PHP library for calculating the display width of Unicode graphemes in terminal environments.
Accurately determine how many columns a character will occupy in the terminal, including complex emoji, combining marks,
and more.

This library was built to support [Solo](https://github.com/soloterm/solo), your all-in-one Laravel command to tame local development.

## Why Use This Library?

Building CLI applications can be challenging when it comes to handling modern Unicode text:

- Emoji and CJK characters take up 2 cells in most terminals
- Zero-width characters (joiners, marks, etc.) don't affect layout but can cause width calculation errors
- Complex text like emoji with skin tones or flags require special handling
- PHP's built-in functions don't fully address these edge cases

This library solves these problems by providing an accurate, performant, and thoroughly tested way to determine the
display width of any character or grapheme cluster.

## Installation

```bash
composer require soloterm/grapheme
```

## Usage

```php
use SoloTerm\Grapheme\Grapheme;

// Basic characters (width: 1)
Grapheme::wcwidth('a'); // Returns: 1
Grapheme::wcwidth('Я'); // Returns: 1

// East Asian characters (width: 2)
Grapheme::wcwidth('文'); // Returns: 2
Grapheme::wcwidth('あ'); // Returns: 2

// Emoji (width: 2)
Grapheme::wcwidth('😀'); // Returns: 2
Grapheme::wcwidth('🚀'); // Returns: 2

// Complex emoji with modifiers (width: 2)
Grapheme::wcwidth('👍🏻'); // Returns: 2
Grapheme::wcwidth('👨‍👩‍👧‍👦'); // Returns: 2

// Zero-width characters (width: 0)
Grapheme::wcwidth("\u{200B}"); // Returns: 0 (Zero-width space)

// Characters with combining marks (width: 1)
Grapheme::wcwidth('é'); // Returns: 1
Grapheme::wcwidth("e\u{0301}"); // Returns: 1 (e + combining acute)

// Special cases
Grapheme::wcwidth("⚠\u{FE0E}"); // Returns: 1 (Warning sign in text presentation)
Grapheme::wcwidth("⚠\u{FE0F}"); // Returns: 2 (Warning sign in emoji presentation)

// Empty string (width: 0)
Grapheme::wcwidth(''); // Returns: 0
```

### Cache Management

Results are cached automatically for performance. For long-running processes, you can manage the cache:

```php
// Clear the cache to free memory
Grapheme::clearCache();

// Set maximum cache size (default: 10,000)
// Cache auto-clears when this limit is exceeded
Grapheme::setMaxCacheSize(5000);
```

## Features

- **Highly optimized** for performance with byte-level fast paths and smart caching
- **Memory safe** for long-running processes with configurable cache limits
- **Comprehensive Unicode support** including:
    - CJK (Chinese, Japanese, Korean) characters
    - Emoji (including skin tone modifiers, gender modifiers, flags)
    - Zero-width characters and control codes
    - Combining marks and accents
    - Regional indicators and flags
    - Variation selectors
- **Carefully tested** against a wide range of Unicode characters (180+ assertions)
- **Minimal dependencies** - only requires PHP 8.1+ and an optional intl extension
- **Compatible** with most terminal environments

## Terminal Compatibility

This library aims to match the behavior of `wcwidth()` in modern terminal emulators.

## Requirements

- PHP 8.1 or higher
- The `symfony/polyfill-intl-normalizer` package is included as a dependency
- The `ext-intl` extension is recommended for best performance

## Under the Hood

The library uses a series of optimized patterns and checks to accurately determine character width:

1. **Byte-level fast paths** - Single-byte ASCII, CJK (UTF-8 0xE4-0xE9), and emoji (UTF-8 0xF0 0x9F) are detected by examining raw bytes, avoiding expensive regex operations
2. **Smart caching** - Results are cached with automatic size limiting to prevent memory growth in long-running processes
3. **Special handling** for complex scripts like Devanagari
4. **Smart detection** of emoji and variation selectors
5. **Proper handling** of zero-width joiners (ZWJ) and other invisible characters

Performance benchmarks show ~1.6M uncached calls/sec and ~12M cached calls/sec on modern hardware.

## Testing

```bash
composer test
```

The test suite includes 180+ assertions covering extensive Unicode scenarios including ASCII, CJK, emoji, zero-width characters, variation selectors, and complex ZWJ sequences. Please feel free to add more.

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

## License

The MIT License (MIT).

## Support

This is free! If you want to support me:

- Check out my courses:
    - [Database School](https://databaseschool.com)
    - [Screencasting](https://screencasting.com)
- Help spread the word about things I make

## Related Projects

- [Solo](https://github.com/soloterm/solo) - All-in-one Laravel command for local development
- [Screen](https://github.com/soloterm/screen) - Pure PHP terminal renderer
- [Dumps](https://github.com/soloterm/dumps) - Laravel command to intercept dumps
- [Notify](https://github.com/soloterm/notify) - PHP package for desktop notifications via OSC escape sequences
- [Notify Laravel](https://github.com/soloterm/notify-laravel) - Laravel integration for soloterm/notify
- [TNotify](https://github.com/soloterm/tnotify) - Standalone, cross-platform CLI for desktop notifications
- [VTail](https://github.com/soloterm/vtail) - Vendor-aware tail for Laravel logs

## Credits

Solo was developed by Aaron Francis. If you like it, please let me know!

- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo
