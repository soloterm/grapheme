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

class GraphemeSegmentationTest extends TestCase
{
    #[Test]
    public function split_segments_full_strings_into_grapheme_clusters(): void
    {
        $this->assertSame(["e\u{0301}"], Grapheme::split("e\u{0301}"));
        $this->assertSame(["\u{2764}\u{FE0F}"], Grapheme::split("\u{2764}\u{FE0F}"));
        $this->assertSame(["👨‍👩‍👧‍👦"], Grapheme::split('👨‍👩‍👧‍👦'));
        $this->assertSame(['文', 'A'], Grapheme::split('文A'));
    }

    #[Test]
    public function split_returns_an_empty_array_for_empty_strings(): void
    {
        $this->assertSame([], Grapheme::split(''));
    }

    #[Test]
    public function split_preserves_invalid_bytes_as_single_byte_segments(): void
    {
        $this->assertSame(['A', "\xFF", 'B'], Grapheme::split("A\xFFB"));
        $this->assertSame(["\xE2", '('], Grapheme::split("\xE2("));
        $this->assertSame(["\xE2", "\x94"], Grapheme::split("\xE2\x94"));
    }

    #[Test]
    public function split_chunk_streams_graphemes_across_arbitrary_boundaries(): void
    {
        $this->assertSame(['─'], $this->streamChunks(["\xE2\x94", "\x80"]));
        $this->assertSame(["e\u{0301}"], $this->streamChunks(['e', "\u{0301}"]));
        $this->assertSame(["\u{2764}\u{FE0F}"], $this->streamChunks(["\u{2764}", "\u{FE0F}"]));
        $this->assertSame(['👨‍👩‍👧‍👦'], $this->streamChunks(['👨‍', '👩‍👧‍👦']));
        $this->assertSame(['🇺🇸'], $this->streamChunks(['🇺', '🇸']));
    }

    #[Test]
    public function split_chunk_preserves_invalid_bytes_without_growing_carry_forever(): void
    {
        $this->assertSame(["\xFF", 'A'], $this->streamChunks(["\xFF", 'A']));
        $this->assertSame(["\xE2", '('], $this->streamChunks(["\xE2", '(']));
        $this->assertSame(["\xE2", "\x94"], $this->streamChunks(["\xE2", "\x94"]));
    }

    #[Test]
    public function split_chunk_uses_an_empty_chunk_as_the_flush_signal(): void
    {
        $first = Grapheme::splitChunk('', 'A');

        $this->assertSame([], $first['graphemes']);
        $this->assertSame('A', $first['carry']);

        $flushed = Grapheme::splitChunk($first['carry'], '');

        $this->assertSame(['A'], $flushed['graphemes']);
        $this->assertSame('', $flushed['carry']);
    }

    #[Test]
    public function split_chunk_keeps_partial_utf8_suffixes_in_carry_until_they_are_complete(): void
    {
        $partial = Grapheme::splitChunk('', "\xE2\x94");

        $this->assertSame([], $partial['graphemes']);
        $this->assertSame("\xE2\x94", $partial['carry']);

        $completed = Grapheme::splitChunk($partial['carry'], "\x80");

        $this->assertSame([], $completed['graphemes']);
        $this->assertSame('─', $completed['carry']);

        $flushed = Grapheme::splitChunk($completed['carry'], '');

        $this->assertSame(['─'], $flushed['graphemes']);
        $this->assertSame('', $flushed['carry']);
    }

    /**
     * @param  list<string>  $chunks
     * @return list<string>
     */
    private function streamChunks(array $chunks): array
    {
        $carry = '';
        $graphemes = [];

        foreach ($chunks as $chunk) {
            $result = Grapheme::splitChunk($carry, $chunk);
            $graphemes = [...$graphemes, ...$result['graphemes']];
            $carry = $result['carry'];
        }

        $result = Grapheme::splitChunk($carry, '');
        $graphemes = [...$graphemes, ...$result['graphemes']];

        $this->assertSame('', $result['carry']);

        return $graphemes;
    }
}
