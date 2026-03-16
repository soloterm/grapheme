---
title: Unicode Support
description: Detailed information about Unicode character width support.
---

# Unicode Support

Grapheme handles a wide variety of Unicode characters and sequences. This page details the supported character types and their widths.

## Width Categories

### Width 0: Zero-Width Characters

Characters that don't occupy any space:

```php
// Zero-width space
Grapheme::wcwidth("\u{200B}");  // 0

// Zero-width joiner (ZWJ)
Grapheme::wcwidth("\u{200D}");  // 0

// Zero-width non-joiner
Grapheme::wcwidth("\u{200C}");  // 0

// Byte order mark
Grapheme::wcwidth("\u{FEFF}");  // 0

// Combining marks (when standalone)
Grapheme::wcwidth("\u{0301}");  // 0 (combining acute accent)
```

### Width 1: Single-Width Characters

Most alphabetic characters:

```php
// ASCII
Grapheme::wcwidth('A');  // 1
Grapheme::wcwidth('z');  // 1
Grapheme::wcwidth('5');  // 1
Grapheme::wcwidth('@');  // 1

// Latin Extended
Grapheme::wcwidth('é');  // 1
Grapheme::wcwidth('ñ');  // 1
Grapheme::wcwidth('ü');  // 1

// Greek
Grapheme::wcwidth('α');  // 1
Grapheme::wcwidth('Ω');  // 1

// Cyrillic
Grapheme::wcwidth('Я');  // 1
Grapheme::wcwidth('ж');  // 1

// Hebrew
Grapheme::wcwidth('א');  // 1

// Arabic
Grapheme::wcwidth('ا');  // 1

// Box drawing
Grapheme::wcwidth('─');  // 1
Grapheme::wcwidth('│');  // 1
Grapheme::wcwidth('┌');  // 1
```

### Width 2: Double-Width Characters

East Asian and emoji characters:

```php
// Chinese
Grapheme::wcwidth('中');  // 2
Grapheme::wcwidth('文');  // 2
Grapheme::wcwidth('字');  // 2

// Japanese Hiragana
Grapheme::wcwidth('あ');  // 2
Grapheme::wcwidth('か');  // 2

// Japanese Katakana
Grapheme::wcwidth('ア');  // 2
Grapheme::wcwidth('カ');  // 2

// Korean Hangul
Grapheme::wcwidth('한');  // 2
Grapheme::wcwidth('글');  // 2

// Emoji
Grapheme::wcwidth('😀');  // 2
Grapheme::wcwidth('🚀');  // 2
Grapheme::wcwidth('❤️');  // 2
```

## Emoji Handling

### Simple Emoji

```php
Grapheme::wcwidth('😀');   // 2
Grapheme::wcwidth('🎉');   // 2
Grapheme::wcwidth('👍');   // 2
```

### Emoji with Skin Tone Modifiers

Skin tone modifiers combine with the base emoji:

```php
Grapheme::wcwidth('👍🏻');  // 2 (light skin)
Grapheme::wcwidth('👍🏿');  // 2 (dark skin)
Grapheme::wcwidth('👋🏽');  // 2 (medium skin)
```

### ZWJ (Zero-Width Joiner) Sequences

Complex emoji made of multiple code points:

```php
// Family emoji
Grapheme::wcwidth('👨‍👩‍👧');      // 2
Grapheme::wcwidth('👨‍👩‍👧‍👦');    // 2

// Profession emoji
Grapheme::wcwidth('👨‍💻');     // 2 (man technologist)
Grapheme::wcwidth('👩‍🚀');     // 2 (woman astronaut)

// Flag + rainbow
Grapheme::wcwidth('🏳️‍🌈');     // 2 (rainbow flag)
```

### Flag Sequences

Flags are formed by regional indicator pairs:

```php
Grapheme::wcwidth('🇺🇸');  // 2 (US flag)
Grapheme::wcwidth('🇯🇵');  // 2 (Japan flag)
Grapheme::wcwidth('🇧🇷');  // 2 (Brazil flag)
```

## Variation Selectors

Some characters can appear in text or emoji presentation:

```php
// Text presentation (VS15: U+FE0E)
Grapheme::wcwidth("⚠\u{FE0E}");  // 1 (warning sign as text)

// Emoji presentation (VS16: U+FE0F)
Grapheme::wcwidth("⚠\u{FE0F}");  // 2 (warning sign as emoji)
```

## Combining Marks

Characters with combining diacritical marks:

```php
// Pre-composed (NFC)
Grapheme::wcwidth('é');           // 1

// Decomposed (NFD)
Grapheme::wcwidth("e\u{0301}");   // 1 (e + combining acute)

// Multiple combining marks
Grapheme::wcwidth("ṩ");           // 1 (s + dot below + dot above)
```

Grapheme normalizes to NFC form when needed for accurate width calculation.

## Special Scripts

### Devanagari

```php
Grapheme::wcwidth('क');   // 1
Grapheme::wcwidth('का');  // 1 (consonant + vowel sign)
```

### Thai

```php
Grapheme::wcwidth('ก');   // 1
```

### Arabic

```php
Grapheme::wcwidth('ا');   // 1
Grapheme::wcwidth('ب');   // 1
```

## Control Characters

ASCII control characters are treated as width 1 by `wcwidth()`. In terminal applications they usually affect layout
through higher-level control flow rather than as printable graphemes, so callers often handle them separately.

```php
Grapheme::wcwidth("\t");      // 1 (tab)
Grapheme::wcwidth("\n");      // 1 (newline)
Grapheme::wcwidth("\u{00}");  // 1 (null)
```

## Terminal Compatibility

Grapheme aims to match the behavior of `wcwidth()` in modern terminal emulators. Width calculations are compatible with:

- iTerm2
- Kitty
- WezTerm
- Ghostty
- GNOME Terminal
- Alacritty
- Most VTE-based terminals

Some older terminals may render characters differently. Grapheme follows the current Unicode standard.

## Edge Cases

### Empty String

```php
Grapheme::wcwidth('');  // 0
```

### Unknown Characters

Characters not in any category default to width 1 (using `mb_strwidth` as fallback).

### Malformed UTF-8

Invalid UTF-8 bytes do not throw during segmentation. `split()` emits them as single-byte segments, and `splitChunk()`
keeps incomplete trailing bytes in `carry` until they can be completed or flushed at EOF.

## Next Steps

- [API Reference](api-reference) - Complete method documentation
- [Usage](usage) - Practical examples
