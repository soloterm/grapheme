<?php

/** @noinspection PhpComposerExtensionStubsInspection */

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SoloTerm\Grapheme;

use Normalizer;

class Grapheme
{
    /**
     * Cache of previously calculated widths.
     *
     * @var array<string, int>
     */
    public static array $cache = [];

    /**
     * Current cache size (avoids count() on every call).
     */
    protected static int $cacheSize = 0;

    /**
     * Maximum cache size before automatic cleanup.
     * Prevents unbounded memory growth in long-running processes.
     */
    protected static int $maxCacheSize = 10000;

    protected static $maybeNeedsNormalizationPattern = '/[\p{M}\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}]/u';

    protected static $specialCharsPattern = '/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}\x{034F}\x{061C}\x{202A}-\x{202E}]|[\p{M}\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}]|[\x{FE0E}\x{FE0F}]|[\x{1F000}-\x{1FFFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]|[\x{1100}-\x{11FF}\x{3000}-\x{303F}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}]/u';

    protected static $variationSelectorsPattern = '/[\x{FE0E}\x{FE0F}]/u';

    protected static $emojiPattern = '/[\x{1F000}-\x{1FFFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

    protected static $eastAsianPattern = '/[\x{1100}-\x{11FF}\x{3000}-\x{303F}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}]/u';

    protected static $textStyleEmojiPattern = '/^[\x{2600}-\x{26FF}\x{2700}-\x{27BF}]$/u';

    protected static $flagSequencePattern = '/\p{Regional_Indicator}{2}|\x{1F3F4}[\x{E0060}-\x{E007F}]+/u';

    protected static $asciiZwjPattern = '/^[\x00-\x7F][\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}]+$/u';

    protected static $devanagariPattern = '/\p{Devanagari}/u';

    protected static $singleLetterWithCombiningMarksPattern = '/^\p{L}\p{M}+$/u';

    protected static $skinTonePattern = '/[\x{1F3FB}-\x{1F3FF}]/u';

    protected static $flagEmojiPattern = '/^[\x{1F1E6}-\x{1F1FF}]{2}$/u';

    protected static $emojiZwjPattern = '/^[\x{1F300}-\x{1F6FF}][\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}]+$/u';

    protected static $emojiWithZwjPattern = '/[\x{1F300}-\x{1F6FF}]/u';

    // Compiled patterns for filtering
    protected static $zwjFilterPattern = '/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}\x{034F}\x{061C}\x{202A}-\x{202E}]+/u';

    protected static $onlyCombiningMarksPattern = '/^[\p{M}\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}]+$/u';

    protected static $baseCharCombiningZwjPattern = '/^\p{L}[\p{M}\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}]+[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}\x{034F}\x{061C}\x{202A}-\x{202E}]+$/u';

    protected static $hasZeroWidthPattern = '/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{2060}-\x{2064}\x{034F}\x{180E}\x{180B}-\x{180D}\x{061C}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FFF9}-\x{FFFB}\x{1160}\x{115F}\x{3164}]/u';

    protected static $textPresentationSymbolsPattern = '/^[\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F100}-\x{1F1FF}]$/u';

    /**
     * Clear the width cache.
     *
     * Useful for long-running processes to free memory, or for testing.
     */
    public static function clearCache(): void
    {
        static::$cache = [];
        static::$cacheSize = 0;
    }

    /**
     * Set the maximum cache size.
     *
     * When the cache exceeds this size, it will be cleared to prevent
     * unbounded memory growth in long-running processes.
     */
    public static function setMaxCacheSize(int $size): void
    {
        static::$maxCacheSize = $size;
    }

    /**
     * Cache a width value and return it.
     */
    protected static function cache(string $grapheme, int $width): int
    {
        static::$cacheSize++;

        return static::$cache[$grapheme] = $width;
    }

    /**
     * Calculate the display width of a Unicode grapheme in terminal columns.
     *
     * @param  string  $grapheme  A single grapheme cluster
     * @return int The display width (0, 1, or 2 columns)
     */
    public static function wcwidth(string $grapheme): int
    {
        // Handle empty string explicitly
        if ($grapheme === '') {
            return 0;
        }

        // Check cache first (fastest path)
        if (isset(static::$cache[$grapheme])) {
            return static::$cache[$grapheme];
        }

        // Prevent unbounded cache growth in long-running processes
        if (static::$cacheSize >= static::$maxCacheSize) {
            static::$cache = [];
            static::$cacheSize = 0;
        }

        $len = strlen($grapheme);

        // Fast path for single-byte ASCII (most common case)
        // ASCII bytes are 0x00-0x7F, and single-byte strings are pure ASCII
        if ($len === 1) {
            return static::cache($grapheme, 1);
        }

        // Fast path for pure ASCII multi-char: all bytes < 0x80
        // Check first byte - if it's ASCII, likely all are (common case)
        $firstByte = ord($grapheme[0]);
        if ($firstByte < 0x80 && strlen($grapheme) === mb_strlen($grapheme)) {
            return static::cache($grapheme, 1);
        }

        // Fast path for common CJK characters (3-byte UTF-8 sequences)
        // CJK Unified Ideographs: U+4E00-U+9FFF → UTF-8: E4 B8 80 to E9 BF BF
        if ($len === 3 && $firstByte >= 0xE4 && $firstByte <= 0xE9) {
            $secondByte = ord($grapheme[1]);
            if ($secondByte >= 0x80 && $secondByte <= 0xBF) {
                return static::cache($grapheme, 2);
            }
        }

        // Fast path for common emoji (4-byte UTF-8 sequences starting with F0 9F)
        if ($len >= 4 && $firstByte === 0xF0 && ord($grapheme[1]) === 0x9F) {
            return static::cache($grapheme, 2);
        }

        // Fast path: zero-width character check for single characters
        if (mb_strlen($grapheme) === 1 && preg_match(static::$hasZeroWidthPattern, $grapheme)) {
            return static::cache($grapheme, 0);
        }

        // Handle ASCII + Zero Width sequences (like 'a‍')
        if (preg_match(static::$asciiZwjPattern, $grapheme)) {
            return static::cache($grapheme, 1);
        }

        // Check for special flag sequence patterns (Scotland, England, etc.)
        if (preg_match(static::$flagSequencePattern, $grapheme)) {
            return static::cache($grapheme, 2);
        }

        // Devanagari conjuncts and other complex scripts
        if (preg_match(static::$devanagariPattern, $grapheme)) {
            return static::cache($grapheme, 1);
        }

        // Only normalize if there's a chance of combining marks
        if (preg_match(static::$maybeNeedsNormalizationPattern, $grapheme)) {
            $grapheme = Normalizer::normalize($grapheme, Normalizer::NFC);
        }

        // Special cases for characters followed by ZWJ/ZWNJ
        if (mb_strpos($grapheme, "\u{200D}") !== false || mb_strpos($grapheme, "\u{200C}") !== false) {
            // Check if it's a single character + ZWJ sequence
            if (mb_strlen(preg_replace(static::$zwjFilterPattern, '', $grapheme)) === 1) {
                // If it's an emoji + ZWJ, it should be width 2
                if (preg_match(static::$emojiZwjPattern, $grapheme)) {
                    return static::cache($grapheme, 2);
                }

                // If it's a CJK/wide char + ZWJ, it should be width 2
                if (preg_match(static::$eastAsianPattern, mb_substr($grapheme, 0, 1))) {
                    return static::cache($grapheme, 2);
                }

                // Otherwise, it should be width 1 (ASCII, Latin, etc. + ZWJ)
                return static::cache($grapheme, 1);
            }

            // If it's an emoji ZWJ sequence
            if (preg_match(static::$emojiWithZwjPattern, $grapheme)) {
                return static::cache($grapheme, 2);
            }
        }

        // Handle variation selectors
        if (preg_match(static::$variationSelectorsPattern, $grapheme)) {
            $baseChar = preg_replace(static::$variationSelectorsPattern, '', $grapheme);

            // Text style variation selector for emoji-capable symbols
            if (mb_strpos($grapheme, "\u{FE0E}") !== false) {
                // Check if it's an emoji-capable character
                if (preg_match(static::$textStyleEmojiPattern, $baseChar)) {
                    return static::cache($grapheme, 1);
                }
            }

            // Check if emoji with variation selector
            if (preg_match(static::$emojiPattern, $baseChar)) {
                return static::cache($grapheme, 2);
            }

            // Check if East Asian character with variation selector
            if (preg_match(static::$eastAsianPattern, $baseChar)) {
                return static::cache($grapheme, 2);
            }

            // Otherwise, measure the base character
            $width = mb_strwidth($baseChar, 'UTF-8');

            return static::cache($grapheme, ($width !== false && $width > 0) ? $width : 1);
        }

        // Check if the grapheme contains any zero-width characters
        $hasZeroWidth = preg_match(static::$hasZeroWidthPattern, $grapheme) === 1;

        // If it has zero-width characters, we need special handling
        if ($hasZeroWidth) {
            // First, handle text with just formatting characters
            $filtered = preg_replace(static::$zwjFilterPattern, '', $grapheme);

            // If nothing is left after removing zero-width chars, or only combining marks left
            if ($filtered === '' || preg_match(static::$onlyCombiningMarksPattern, $filtered)) {
                return static::cache($grapheme, 0);
            }

            // Handle base char + combining marks + ZWJ
            if (preg_match(static::$baseCharCombiningZwjPattern, $grapheme)) {
                return static::cache($grapheme, 1);
            }

            // If it's a single character + zero-width chars
            if (mb_strlen($filtered) === 1) {
                if (preg_match(static::$eastAsianPattern, $filtered)) {
                    return static::cache($grapheme, 2);
                }

                return static::cache($grapheme, 1);
            }
        }

        // Check for special characters - if none, do direct width calculation
        if (!preg_match(static::$specialCharsPattern, $grapheme)) {
            $width = mb_strwidth($grapheme, 'UTF-8');

            return static::cache($grapheme, ($width !== false && $width > 0) ? $width : 1);
        }

        // Single letter followed by combining marks
        if (preg_match(static::$singleLetterWithCombiningMarksPattern, $grapheme)) {
            return static::cache($grapheme, 1);
        }

        // Handle skin tones or flags (single grapheme)
        if (grapheme_strlen($grapheme) === 1) {
            if (preg_match(static::$skinTonePattern, $grapheme)) {
                return static::cache($grapheme, 2);
            }
            if (preg_match(static::$flagEmojiPattern, $grapheme)) {
                return static::cache($grapheme, 2);
            }
        }

        // Handle symbols that should be width 1 in text presentation
        if (preg_match(static::$textPresentationSymbolsPattern, $grapheme) && mb_strpos($grapheme, "\u{FE0F}") === false) {
            return static::cache($grapheme, 1);
        }

        // Default fallback to mb_strwidth, carefully filtering zero-width characters
        $filtered = preg_replace(static::$zwjFilterPattern, '', $grapheme);
        $width = mb_strwidth($filtered, 'UTF-8');

        return static::cache($grapheme, ($width !== false && $width > 0) ? $width : 1);
    }
}
