# SoloTerm Grapheme

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
```

## Features

- **Highly optimized** for performance with early-return paths and smart caching
- **Comprehensive Unicode support** including:
    - CJK (Chinese, Japanese, Korean) characters
    - Emoji (including skin tone modifiers, gender modifiers, flags)
    - Zero-width characters and control codes
    - Combining marks and accents
    - Regional indicators and flags
    - Variation selectors
- **Carefully tested** against a wide range of Unicode characters
- **Minimal dependencies** - only requires PHP 8.2+ and an optional intl extension
- **Compatible** with most terminal environments

## Terminal Compatibility

This library aims to match the behavior of `wcwidth()` in modern terminal emulators.

## Requirements

- PHP 8.2 or higher
- The `symfony/polyfill-intl-normalizer` package is included as a dependency
- The `ext-intl` extension is recommended for best performance

## Under the Hood

The library uses a series of optimized patterns and checks to accurately determine character width:

1. Fast paths for ASCII and zero-width characters
2. Special handling for complex scripts like Devanagari
3. Smart detection of emoji and variation selectors
4. Proper handling of zero-width joiners (ZWJ) and other invisible characters
5. Caching of results for improved performance

## Testing

```bash
composer test
```

The test suite includes over 150 different test cases covering many possible Unicode scenarios. Please feel free to add
more.

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

## License

The MIT License (MIT).

## Support

This is free! If you want to support me:

- Sponsor my open source work: [aaronfrancis.com/backstage](https://aaronfrancis.com/backstage)
- Check out my courses:
    - [Mastering Postgres](https://masteringpostgres.com)
    - [High Performance SQLite](https://highperformancesqlite.com)
    - [Screencasting](https://screencasting.com)
- Help spread the word about things I make

## Credits

Solo was developed by Aaron Francis. If you like it, please let me know!

- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo
