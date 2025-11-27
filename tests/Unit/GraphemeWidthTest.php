<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 *
 * @link https://aaronfrancis.com
 * @link https://x.com/aarondfrancis
 */

namespace SoloTerm\Grapheme\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SoloTerm\Grapheme\Grapheme;

class GraphemeWidthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test to ensure isolation
        Grapheme::clearCache();
    }

    #[Test]
    public function empty_string_returns_zero(): void
    {
        $this->assertSame(0, Grapheme::wcwidth(''));
    }

    #[Test]
    public function cache_can_be_cleared(): void
    {
        // Populate cache
        Grapheme::wcwidth('a');
        Grapheme::wcwidth('文');

        $this->assertNotEmpty(Grapheme::$cache);

        // Clear cache
        Grapheme::clearCache();

        $this->assertEmpty(Grapheme::$cache);
    }

    #[Test]
    public function cache_auto_clears_when_max_size_exceeded(): void
    {
        // Set a small max cache size
        Grapheme::setMaxCacheSize(5);

        // Fill cache beyond max size
        for ($i = 0; $i < 10; $i++) {
            Grapheme::wcwidth(chr(65 + $i)); // A, B, C, ...
        }

        // Cache should have been cleared and only contain recent entries
        $this->assertLessThanOrEqual(5, count(Grapheme::$cache));

        // Reset to default
        Grapheme::setMaxCacheSize(10000);
    }

    #[Test]
    public function benchmark_performance(): void
    {
        $iterations = 10000;

        // Test data representing common use cases
        $testChars = [
            'a', 'Z', ' ', '@',           // ASCII (most common)
            'é', 'ñ', 'ü',                // Latin with accents
            '文', '字', 'あ', '한',         // CJK (width 2)
            '😀', '🚀', '👍',              // Emoji (width 2)
            '👨‍👩‍👧‍👦',                        // Complex ZWJ sequence
            "\u{200B}",                   // Zero-width
        ];

        // Warm up cache
        foreach ($testChars as $char) {
            Grapheme::wcwidth($char);
        }

        // Benchmark cached lookups
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testChars as $char) {
                Grapheme::wcwidth($char);
            }
        }
        $cachedTime = (microtime(true) - $start) * 1000;

        // Clear cache and benchmark uncached
        Grapheme::clearCache();
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Grapheme::clearCache(); // Force recalculation
            foreach ($testChars as $char) {
                Grapheme::wcwidth($char);
            }
        }
        $uncachedTime = (microtime(true) - $start) * 1000;

        $totalCalls = $iterations * count($testChars);
        echo "\n\nPerformance Benchmark ({$totalCalls} calls):\n";
        echo '  Cached:   ' . round($cachedTime, 2) . ' ms (' . round($totalCalls / $cachedTime * 1000) . " calls/sec)\n";
        echo '  Uncached: ' . round($uncachedTime, 2) . ' ms (' . round($totalCalls / $uncachedTime * 1000) . " calls/sec)\n";
        echo '  Speedup:  ' . round($uncachedTime / $cachedTime, 1) . "x\n";

        // Cache should be significantly faster
        $this->assertLessThan($uncachedTime, $cachedTime);
    }

    #[Test]
    public function test_grapheme_display_width_extensive(): void
    {
        // Create an array of characters to test with their expected widths
        $testCases = [
            // ASCII characters (width: 1)
            ['a', 1],
            ['Z', 1],
            ['0', 1],
            ['@', 1],
            [' ', 1],

            ["e\u{0301}", 1],

            // Latin with combining marks (width: 1)
            ['é', 1], // e + combining acute
            ["e\u{0301}", 1], // e + combining acute (decomposed)
            ['ñ', 1], // n + combining tilde
            ["n\u{0303}", 1], // n + combining tilde (decomposed)
            ['ç', 1], // c + cedilla
            ['ö', 1], // o + diaeresis
            ['Å', 1], // A + ring
            ['ü', 1], // u + diaeresis

            // Multiple combining marks (width: 1)
            ["a\u{0301}\u{0308}", 1], // a + acute + diaeresis
            ["o\u{0302}\u{0303}", 1], // o + circumflex + tilde

            // CJK and wide characters (width: 2)
            ['文', 2], // Chinese character
            ['字', 2], // Chinese character
            ['あ', 2], // Hiragana
            ['カ', 2], // Katakana
            ['한', 2], // Korean
            ['ﾜ', 1], // Half-width Katakana (width: 1)
            ['Ａ', 2], // Full-width Latin A
            ['１', 2], // Full-width digit

            // Emojis (width: 2)
            ['😀', 2], // Basic emoji (GRINNING FACE)
            ['🙂', 2], // SLIGHTLY SMILING FACE
            ['👍', 2], // THUMBS UP
            ['🚀', 2], // ROCKET
            ['🌍', 2], // EARTH GLOBE EUROPE-AFRICA

            // Emoji with modifiers (width: 2)
            ['👍🏻', 2], // THUMBS UP + light skin tone
            ['👍🏿', 2], // THUMBS UP + dark skin tone
            ['❤️', 2], // RED HEART + variation selector (emoji style)
            ['♥️', 2], // BLACK HEART SUIT + variation selector
            ['⭐', 2], // Star

            // Flag emojis (width: 2)
            ['🇺🇸', 2], // US flag
            ['🇯🇵', 2], // Japan flag
            ['🇫🇷', 2], // France flag
            ['🇪🇺', 2], // EU flag

            // Emoji with ZWJ sequences (width: 2)
            ['👨‍👩‍👧‍👦', 2], // Family (man, woman, girl, boy)
            ['👩‍💻', 2], // Woman technologist
            ['👨‍🚀', 2], // Man astronaut
            ['👩‍🔬', 2], // Woman scientist
            ['🏳️‍🌈', 2], // Rainbow flag
            ['👨🏻‍🤝‍👨🏿', 2], // Men holding hands with different skin tones

            // Zero-width characters (width: 0)
            ["\u{200B}", 0], // Zero-width space
            ["\u{200C}", 0], // Zero-width non-joiner
            ["\u{200D}", 0], // Zero-width joiner
            ["\u{FEFF}", 0], // Zero-width non-breaking space

            // ASCII + ZWJ combinations(width: 1)
            ['a' . "\u{200D}", 1], // a + ZWJ
            ['x' . "\u{200D}" . "\u{200D}", 1], // x + multiple ZWJ
            ['y' . "\u{200D}" . "\u{200C}", 1], // y + ZWJ + ZWNJ

            // Variation selectors
            ['텍' . "\u{FE0E}", 2], // Text style variation
            ['텍' . "\u{FE0F}", 2], // Emoji style variation

            // Right-to-left characters (width: 1)
            ['א', 1], // Hebrew
            ['ב', 1], // Hebrew
            ['ج', 1], // Arabic
            ['د', 1], // Arabic

            // Punctuation
            ['—', 1], // Em dash
            ['…', 1], // Ellipsis
            ['→', 1], // Right arrow
            ['★', 1], // Star

            // Complex scripts
            ['ก', 1], // Thai
            ['ข', 1], // Thai
            ['ǟ', 1], // Latin extended
            ['Ω', 1], // Greek
            ['Я', 1], // Cyrillic
            ['ई', 1], // Devanagari
            ['ਗ', 1], // Gurmukhi
            ['ધ', 1], // Gujarati
            ['ม', 1], // Thai
            ['ည', 1], // Myanmar
            ['ខ', 1], // Khmer

            // Special cases
            ["\t", 1], // Tab (handled separately in your code)
            ["\n", 1], // Newline
            ["\r", 1], // Carriage return

            // Control characters
            ["\x07", 1], // Bell
            ["\x1B", 1], // Escape

            // Various combined sequences
            ['é' . "\u{200D}", 1], // Accented char + ZWJ
            ['文' . "\u{200D}", 2], // Wide char + ZWJ
            ['👍' . "\u{200D}", 2], // Emoji + ZWJ

            // More complex combinations
            ['a' . "\u{0301}" . "\u{200D}", 1], // a + combining acute + ZWJ

            // Numbers and special forms
            ['①', 1], // Circled digit
            ['⑩', 1], // Circled number
            ['⒈', 1], // Parenthesized number
            ['Ⅷ', 1], // Roman numeral

            // Mathematical symbols
            ['∑', 1], // Summation
            ['∞', 1], // Infinity
            ['√', 1], // Square root
            ['∫', 1], // Integral

            // Currency symbols
            ['$', 1], // Dollar
            ['€', 1], // Euro
            ['£', 1], // Pound
            ['¥', 1], // Yen
            ['₿', 1], // Bitcoin

            // Technical symbols
            ['©', 1], // Copyright
            ['®', 1], // Registered
            ['™', 1], // Trademark

            // Box drawing
            ['┌', 1], // Box drawing light down and right
            ['│', 1], // Box drawing light vertical
            ['┘', 1], // Box drawing light up and left
            ['═', 1], // Box drawing double horizontal
            ['▓', 1], // Dark shade
            ['■', 1], // Black square

            // Musical symbols
            ['♩', 1], // Quarter note
            ['♪', 1], // Eighth note
            ['♫', 1], // Beamed eighth notes

            // Geometric shapes
            ['◆', 1], // Black diamond
            ['●', 1], // Black circle
            ['▲', 1], // Black up-pointing triangle

            // More rare emojis and sequences
            ['🕴️', 2], // Man in business suit levitating
            ['🧜‍♀️', 2], // Mermaid
            ['🦹‍♂️', 2], // Man supervillain

            // Uncommon language scripts
            ['ᠠ', 1], // Mongolian
            ['ᚠ', 1], // Runic
            ['ϡ', 1], // Coptic
            ['ჯ', 1], // Georgian
            ['Ꮿ', 1], // Cherokee

            // Extremely rare characters
            ['𓂀', 1], // Egyptian hieroglyph
            ['𐎠', 1], // Old Persian
            ['𐏊', 1], // Ugaritic
            ['𐡀', 1], // Imperial Aramaic

            // Spaces and variants
            [' ', 1], // Regular space
            ["\u{00A0}", 1], // Non-breaking space
            ["\u{2002}", 1], // En space
            ["\u{2003}", 1], // Em space
            ["\u{2007}", 1], // Figure space
            ["\u{202F}", 1], // Narrow no-break space

            // Ligatures (typically single width)
            ['ﬀ', 1], // Latin small ligature ff
            ['ﬁ', 1], // Latin small ligature fi
            ['ﬂ', 1], // Latin small ligature fl

            // Invisible control characters (width should be 1 as they take a cell in terminal)
            ["\x00", 1], // Null character
            ["\x01", 1], // Start of Heading
            ["\x1F", 1], // Unit Separator

            // More combining mark sequences with unusual ordering
            ["a\u{0308}\u{0301}", 1], // a + diaeresis + acute (unusual order)
            ["o\u{035C}\u{035B}", 1], // o + double breve below + zigzag above

            // Double-width punctuation in CJK context
            ['，', 2], // Fullwidth Comma
            ['。', 2], // Ideographic Full Stop
            ['「', 2], // Left Corner Bracket
            ['」', 2], // Right Corner Bracket

            // Letterlike symbols
            ["\u{2122}", 1], // Trade Mark Sign (™ in alternate form)
            ["\u{2126}", 1], // Ohm Sign (Ω in alternate form)

            // More emoji variation selectors tests
            ["⚠\u{FE0E}", 1], // Warning Sign with text style
            ["⚠\u{FE0F}", 2], // Warning Sign with emoji style
            ["☎\u{FE0E}", 1], // Black Telephone with text style
            ["☎\u{FE0F}", 2], // Black Telephone with emoji style

            // Emoji modifier tests
            ["👋\u{1F3FB}", 2], // Waving Hand: Light Skin Tone
            ["👋\u{1F3FC}", 2], // Waving Hand: Medium-Light Skin Tone
            ["👋\u{1F3FD}", 2], // Waving Hand: Medium Skin Tone

            // Non-standard flag sequences
            ['🏴󠁧󠁢󠁳󠁣󠁴󠁿', 2], // Scotland flag (complex sequence)
            ['🏴󠁧󠁢󠁥󠁮󠁧󠁿', 2], // England flag (complex sequence)

            // Dingbats style characters
            ["\u{2756}", 1], // Black Diamond Minus White X
            ["\u{2600}", 1], // Black Sun with Rays (text presentation)
            ["\u{2600}\u{FE0F}", 2], // Black Sun with Rays (emoji presentation)

            // Grapheme clusters with keycap sequence
            ["1\u{FE0F}\u{20E3}", 2], // Keycap digit one
            ["#\u{FE0F}\u{20E3}", 2], // Keycap number sign

            // Complex emoji with skin tone AND gender
            ["👩\u{1F3FD}\u{200D}\u{1F680}", 2], // Woman astronaut with medium skin tone

            // Rare emoji sequences with multiple ZWJs
            ["👨\u{200D}\u{1F468}\u{200D}\u{1F466}", 2], // Family: Man, Man, Boy

            // Recent additions to Unicode
            ['🦩', 2], // Flamingo
            ['🧬', 2], // DNA
            ['🪐', 2], // Ringed Planet

            // Unusual punctuation and symbols
            ["\u{2E2E}", 1], // Reversed Question Mark
            ["\u{203B}", 1], // Reference Mark
            ["\u{267F}", 1], // Wheelchair Symbol (text presentation)
            ["\u{267F}\u{FE0F}", 2], // Wheelchair Symbol (emoji presentation)

            // Symbols with variation selector that should remain width 1
            ["\u{2194}\u{FE0E}", 1], // Left-Right Arrow with text presentation
            ["\u{203C}\u{FE0E}", 1], // Double Exclamation Mark with text presentation

            // Testing joined graphemes (normally handled by Unicode segmentation but good to verify)
            ['क्ष', 1], // Devanagari KA + virama + SSA (forms a conjunct)

            // More zero-width characters
            ["\u{034F}", 0], // Combining Grapheme Joiner
            ["\u{061C}", 0], // Arabic Letter Mark
            ["\u{2028}", 1], // Line Separator (should be width 1 in terminal)
            ["\u{202A}", 0], // Left-to-Right Embedding
            ["\u{202D}", 0], // Left-to-Right Override

            // Wacky combinations that might appear in real text
            ["a\u{0308}\u{0301}\u{034F}", 1], // a + diaeresis + acute + CGJ
            ["👍\u{1F3FB}\u{FE0E}", 2], // Thumbs up + skin tone + text presentation (unusual but possible)
        ];

        // Test each case
        foreach ($testCases as $index => [$grapheme, $expectedWidth]) {
            $actualWidth = Grapheme::wcwidth($grapheme);

            // Output useful debug information on failure
            $this->assertSame(
                $expectedWidth,
                $actualWidth,
                sprintf(
                    "Case %d failed: Grapheme '%s' (hex: %s) expected width %d, got %d",
                    $index,
                    $grapheme,
                    bin2hex($grapheme),
                    $expectedWidth,
                    $actualWidth
                )
            );
        }
    }
}
