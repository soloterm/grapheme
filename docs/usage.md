---
title: Usage
description: Learn how to use Grapheme for width calculation.
---

# Usage

## Basic Width Calculation

### wcwidth()

The primary method for calculating grapheme width:

```php
use SoloTerm\Grapheme\Grapheme;

// Returns 0, 1, or 2
$width = Grapheme::wcwidth($grapheme);
```

### Return Values

| Width | Meaning |
|-------|---------|
| 0 | Zero-width character (invisible) |
| 1 | Single-width character |
| 2 | Double-width character |

### Examples

```php
// Width 1: ASCII and most alphabetic scripts
Grapheme::wcwidth('a');    // 1
Grapheme::wcwidth('Z');    // 1
Grapheme::wcwidth('é');    // 1
Grapheme::wcwidth('Я');    // 1
Grapheme::wcwidth('α');    // 1

// Width 2: CJK and emoji
Grapheme::wcwidth('中');   // 2
Grapheme::wcwidth('日');   // 2
Grapheme::wcwidth('あ');   // 2
Grapheme::wcwidth('😀');   // 2
Grapheme::wcwidth('🚀');   // 2

// Width 0: Zero-width characters
Grapheme::wcwidth("\u{200B}");   // 0 (zero-width space)
Grapheme::wcwidth("\u{200D}");   // 0 (zero-width joiner)
Grapheme::wcwidth("\u{FEFF}");   // 0 (BOM)

// Empty string
Grapheme::wcwidth('');     // 0
```

## Calculating String Width

Calculate the total width of a string:

```php
function stringWidth(string $text): int
{
    $width = 0;

    // Split into grapheme clusters
    $graphemes = grapheme_split($text) ?: [];

    foreach ($graphemes as $grapheme) {
        $width += Grapheme::wcwidth($grapheme);
    }

    return $width;
}

echo stringWidth('Hello');   // 5
echo stringWidth('你好');    // 4
echo stringWidth('Hi 👋');   // 5 (H=1, i=1, space=1, wave=2)
```

## Cache Management

Grapheme caches width calculations for performance.

### Automatic Caching

Results are cached automatically:

```php
// First call: computed
Grapheme::wcwidth('😀');

// Subsequent calls: cached (much faster)
Grapheme::wcwidth('😀');
Grapheme::wcwidth('😀');
```

### Cache Size Limit

The cache has a default limit of 10,000 entries. When exceeded, the cache is cleared automatically to prevent memory growth.

```php
// Set a custom limit
Grapheme::setMaxCacheSize(5000);
```

### Clearing the Cache

For long-running processes, you may want to clear the cache manually:

```php
// Clear all cached results
Grapheme::clearCache();
```

### Inspecting the Cache

The cache is publicly accessible:

```php
// Check cache size
$size = count(Grapheme::$cache);

// Check if a value is cached
if (isset(Grapheme::$cache['😀'])) {
    echo "Emoji is cached\n";
}
```

## Long-Running Processes

For daemons or long-running CLI applications:

```php
class MyDaemon
{
    private int $processedCount = 0;

    public function process(string $text): void
    {
        // Process text...
        foreach (grapheme_split($text) as $grapheme) {
            $width = Grapheme::wcwidth($grapheme);
            // ...
        }

        $this->processedCount++;

        // Periodically clear cache to prevent memory growth
        if ($this->processedCount % 10000 === 0) {
            Grapheme::clearCache();
        }
    }
}
```

Or set a smaller cache limit:

```php
// Limit to 1,000 entries
Grapheme::setMaxCacheSize(1000);
```

## Integration with Screen

Grapheme is used by the [Screen](/docs/screen) package for accurate terminal rendering:

```php
use SoloTerm\Screen\Screen;

$screen = new Screen(80, 24);

// Screen uses Grapheme internally for width calculation
$screen->write('Hello 世界! 🎉');
```

## Performance Tips

### Leverage Caching

Cache hits are ~7x faster than cache misses. For repeated calculations, let the cache work:

```php
// Good: same graphemes get cached
foreach ($lines as $line) {
    foreach (grapheme_split($line) as $grapheme) {
        $width = Grapheme::wcwidth($grapheme);
    }
}

// Avoid: unnecessarily clearing cache between iterations
foreach ($lines as $line) {
    Grapheme::clearCache();  // Don't do this!
    // ...
}
```

### Batch Processing

When processing large amounts of text, the cache automatically handles repetition:

```php
function processDocument(string $document): int
{
    $totalWidth = 0;
    $lines = explode("\n", $document);

    foreach ($lines as $line) {
        foreach (grapheme_split($line) as $grapheme) {
            $totalWidth += Grapheme::wcwidth($grapheme);
        }
    }

    return $totalWidth;
}

// Common characters (spaces, letters) get cached
// Subsequent lines with same characters are fast
```

## Next Steps

- [Unicode Support](unicode-support) - Detailed character type information
- [API Reference](api-reference) - Complete method documentation
