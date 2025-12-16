---
title: Introduction
description: Calculate Unicode grapheme display width for terminal applications.
---

# Grapheme

Grapheme is a highly optimized PHP library for calculating the display width of Unicode graphemes in terminal environments. It determines how many columns a character will occupy when rendered in a terminal.

## The Problem

Building CLI applications with modern Unicode text is challenging:

- **Emoji** occupy 2 cells in most terminals
- **CJK characters** (Chinese, Japanese, Korean) are double-width
- **Zero-width characters** (joiners, marks) don't affect layout
- **Complex sequences** (emoji with skin tones, flags) require special handling
- **PHP's built-in functions** don't handle these cases correctly

```php
// PHP's strlen and mb_strlen don't help with terminal width
strlen('😀');      // 4 (bytes)
mb_strlen('😀');   // 1 (character)
// But in a terminal, it takes 2 columns!
```

## The Solution

Grapheme provides a single function that returns the correct terminal width:

```php
use SoloTerm\Grapheme\Grapheme;

Grapheme::wcwidth('a');    // 1 - regular ASCII
Grapheme::wcwidth('文');   // 2 - CJK character
Grapheme::wcwidth('😀');   // 2 - emoji
Grapheme::wcwidth('👨‍👩‍👧‍👦'); // 2 - complex ZWJ sequence
```

The name `wcwidth` comes from the POSIX C function of the same name.

## Key Features

### Accurate Width Calculation

Returns 0, 1, or 2 for any Unicode grapheme:

| Width | Characters |
|-------|------------|
| 0 | Zero-width joiners, combining marks, control codes |
| 1 | ASCII, Latin, Cyrillic, most alphabetic scripts |
| 2 | CJK, emoji, fullwidth characters |

### High Performance

- **Byte-level fast paths** for common cases (ASCII, CJK, emoji)
- **Smart caching** with automatic size limits
- **~12M ops/sec** for cached lookups
- **~5.5M ops/sec** for uncached lookups

### Comprehensive Unicode Support

- CJK characters (Chinese, Japanese, Korean)
- Emoji with modifiers (skin tones, gender)
- Flag sequences
- Zero-width joiners (ZWJ) sequences
- Combining marks and accents
- Variation selectors

### Memory Safe

Automatic cache management prevents unbounded memory growth in long-running processes.

## Quick Start

Install via Composer:

```bash
composer require soloterm/grapheme
```

Calculate widths:

```php
use SoloTerm\Grapheme\Grapheme;

// Simple characters
Grapheme::wcwidth('A');    // 1
Grapheme::wcwidth('Ω');    // 1

// Double-width characters
Grapheme::wcwidth('中');   // 2
Grapheme::wcwidth('🚀');   // 2

// Zero-width characters
Grapheme::wcwidth("\u{200B}");  // 0 (zero-width space)

// Complex emoji
Grapheme::wcwidth('👍🏻');  // 2 (thumbs up + skin tone)
```

## Use Cases

### Text Alignment

```php
function padToWidth(string $text, int $targetWidth): string
{
    $width = 0;
    foreach (grapheme_split($text) as $grapheme) {
        $width += Grapheme::wcwidth($grapheme);
    }

    $padding = $targetWidth - $width;
    return $text . str_repeat(' ', max(0, $padding));
}

echo padToWidth('Hello', 10);   // "Hello     "
echo padToWidth('你好', 10);    // "你好      " (4 columns for CJK)
```

### Box Drawing

```php
function drawBox(string $content, int $width): void
{
    $contentWidth = 0;
    foreach (grapheme_split($content) as $grapheme) {
        $contentWidth += Grapheme::wcwidth($grapheme);
    }

    $padding = $width - $contentWidth - 4;  // 4 for borders and spaces

    echo "┌" . str_repeat("─", $width - 2) . "┐\n";
    echo "│ " . $content . str_repeat(" ", $padding) . " │\n";
    echo "└" . str_repeat("─", $width - 2) . "┘\n";
}
```

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1 or higher |

The `ext-intl` extension is recommended for best performance.

## Next Steps

- [Installation](installation) - Get started
- [Usage](usage) - Core API and caching
- [Unicode Support](unicode-support) - Character types explained
