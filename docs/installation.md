---
title: Installation
description: How to install the Grapheme package.
---

# Installation

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1 or higher |

### Optional: ext-intl

The `intl` extension is recommended for best performance:

```bash
# Ubuntu/Debian
sudo apt install php-intl

# macOS with Homebrew
brew install php@8.2  # Usually includes intl

# Check if installed
php -m | grep intl
```

Grapheme works without `ext-intl` by using a Symfony polyfill, but the native extension is faster.

## Install via Composer

```bash
composer require soloterm/grapheme
```

## Verify Installation

Test the installation:

```php
<?php

require 'vendor/autoload.php';

use SoloTerm\Grapheme\Grapheme;

// Test basic characters
echo "ASCII 'a': " . Grapheme::wcwidth('a') . "\n";       // 1
echo "CJK '中': " . Grapheme::wcwidth('中') . "\n";       // 2
echo "Emoji '😀': " . Grapheme::wcwidth('😀') . "\n";     // 2
echo "ZWS: " . Grapheme::wcwidth("\u{200B}") . "\n";      // 0

echo "\nInstallation successful!\n";
```

Save as `test-grapheme.php` and run:

```bash
php test-grapheme.php
```

Expected output:

```
ASCII 'a': 1
CJK '中': 2
Emoji '😀': 2
ZWS: 0

Installation successful!
```

## Dependencies

Grapheme has minimal dependencies:

- `symfony/polyfill-intl-normalizer` - Provides Unicode normalization when `ext-intl` is not available

These are installed automatically by Composer.

## Next Steps

- [Usage](usage) - Learn the API
- [Unicode Support](unicode-support) - Understand character widths
