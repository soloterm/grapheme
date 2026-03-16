---
title: API Reference
description: Complete API documentation for the Grapheme class.
---

# API Reference

Complete reference for the Grapheme class.

## Grapheme Class

```php
use SoloTerm\Grapheme\Grapheme;
```

All methods are static.

---

## Methods

### wcwidth()

```php
public static function wcwidth(string $grapheme): int
```

Calculate the display width of a Unicode grapheme cluster.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$grapheme` | string | A single grapheme cluster |

**Returns**: Display width (0, 1, or 2)

| Return Value | Meaning |
|--------------|---------|
| 0 | Zero-width (invisible character) |
| 1 | Single-width (standard character) |
| 2 | Double-width (CJK, emoji) |

**Examples**:

```php
// Width 1
Grapheme::wcwidth('a');     // 1
Grapheme::wcwidth('é');     // 1
Grapheme::wcwidth('Я');     // 1

// Width 2
Grapheme::wcwidth('中');    // 2
Grapheme::wcwidth('😀');    // 2
Grapheme::wcwidth('👨‍👩‍👧'); // 2

// Width 0
Grapheme::wcwidth('');              // 0
Grapheme::wcwidth("\u{200B}");      // 0
Grapheme::wcwidth("\u{200D}");      // 0
```

**Notes**:

- Pass a single grapheme cluster, not a multi-character string
- Use `Grapheme::split()` to split strings into grapheme clusters
- Results are cached automatically for performance

---

### split()

```php
public static function split(string $text): array
```

Split a UTF-8 string into grapheme clusters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$text` | string | UTF-8 text to segment |

**Returns**: `list<string>` of grapheme clusters

For malformed UTF-8, invalid bytes are preserved as single-byte segments so the original byte stream can still be
reconstructed.

**Examples**:

```php
Grapheme::split("e\u{0301}");        // ["é"]
Grapheme::split("\u{2764}\u{FE0F}"); // ["❤️"]
Grapheme::split('👨‍👩‍👧‍👦');    // ["👨‍👩‍👧‍👦"]
Grapheme::split('文A');             // ['文', 'A']
```

---

### splitChunk()

```php
public static function splitChunk(string $carry, string $chunk): array
```

Split streamed UTF-8 input across arbitrary byte boundaries.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$carry` | string | Pending bytes and trailing grapheme from the previous chunk |
| `$chunk` | string | Next raw byte chunk |

**Returns**: `array{graphemes: list<string>, carry: string}`

`carry` keeps the trailing grapheme plus any incomplete UTF-8 suffix so later chunks can finish the cluster safely.
Pass an empty chunk to flush the final grapheme at end-of-input. Invalid UTF-8 bytes are emitted as single-byte
segments instead of throwing.

**Example**:

```php
$carry = '';
$graphemes = [];

foreach (["🇺", "🇸"] as $chunk) {
    $result = Grapheme::splitChunk($carry, $chunk);
    $graphemes = [...$graphemes, ...$result['graphemes']];
    $carry = $result['carry'];
}

$result = Grapheme::splitChunk($carry, '');
$graphemes = [...$graphemes, ...$result['graphemes']];

// ['🇺🇸']
```

---

### clearCache()

```php
public static function clearCache(): void
```

Clear the internal width cache.

**Example**:

```php
// Calculate some widths
Grapheme::wcwidth('a');
Grapheme::wcwidth('中');

// Clear the cache
Grapheme::clearCache();

// Cache is now empty
var_dump(count(Grapheme::$cache));  // 0
```

**When to use**:

- Long-running processes to free memory
- After processing large batches of unique characters
- Testing/debugging

---

### setMaxCacheSize()

```php
public static function setMaxCacheSize(int $size): void
```

Set the maximum number of entries in the cache.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$size` | int | Maximum cache entries (default: 10,000) |

**Behavior**: When the cache exceeds this limit, it is automatically cleared.

**Example**:

```php
// Limit cache to 1,000 entries
Grapheme::setMaxCacheSize(1000);

// For memory-constrained environments
Grapheme::setMaxCacheSize(500);

// For high-throughput applications
Grapheme::setMaxCacheSize(50000);
```

---

## Properties

### $cache

```php
public static array $cache = [];
```

The internal cache mapping graphemes to their widths.

**Type**: `array<string, int>`

**Access**: Read-only recommended (public for inspection/testing)

**Example**:

```php
// Calculate some widths
Grapheme::wcwidth('a');
Grapheme::wcwidth('中');

// Inspect cache
var_dump(Grapheme::$cache);
// [
//     'a' => 1,
//     '中' => 2,
// ]

// Check if cached
if (isset(Grapheme::$cache['😀'])) {
    echo "Emoji is cached\n";
}

// Cache size
echo count(Grapheme::$cache);
```

---

## Usage Patterns

### Calculate String Width

```php
function stringWidth(string $text): int
{
    $width = 0;
    $graphemes = Grapheme::split($text);

    foreach ($graphemes as $grapheme) {
        $width += Grapheme::wcwidth($grapheme);
    }

    return $width;
}
```

### Pad to Width

```php
function padRight(string $text, int $targetWidth): string
{
    $currentWidth = 0;
    $graphemes = Grapheme::split($text);

    foreach ($graphemes as $grapheme) {
        $currentWidth += Grapheme::wcwidth($grapheme);
    }

    $padding = max(0, $targetWidth - $currentWidth);
    return $text . str_repeat(' ', $padding);
}
```

### Truncate to Width

```php
function truncateToWidth(string $text, int $maxWidth, string $suffix = '…'): string
{
    $result = '';
    $width = 0;
    $suffixWidth = Grapheme::wcwidth($suffix);
    $graphemes = Grapheme::split($text);

    foreach ($graphemes as $grapheme) {
        $charWidth = Grapheme::wcwidth($grapheme);

        if ($width + $charWidth + $suffixWidth > $maxWidth) {
            return $result . $suffix;
        }

        $result .= $grapheme;
        $width += $charWidth;
    }

    return $result;
}
```

### Long-Running Process

```php
function processBatch(array $items): void
{
    // Limit cache for memory safety
    Grapheme::setMaxCacheSize(5000);

    foreach ($items as $item) {
        // Process item...
        $width = Grapheme::wcwidth($item->text);
    }

    // Clear cache after batch
    Grapheme::clearCache();
}
```

---

## Performance

### Benchmark Results

Approximate performance on modern hardware:

| Operation | Speed |
|-----------|-------|
| Cached lookup | ~12M ops/sec |
| Uncached (ASCII) | ~5.5M ops/sec |
| Uncached (CJK) | ~3.5M ops/sec |
| Uncached (emoji) | ~2.5M ops/sec |

### Optimization Tips

1. **Let caching work** - Avoid unnecessary `clearCache()` calls
2. **Set appropriate cache size** - Balance memory vs. hit rate
3. **Process in batches** - Cache common characters across operations
4. **Use ext-intl** - Native extension is faster than polyfill
