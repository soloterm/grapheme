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

class GraphemeFuzzTest extends TestCase
{
    #[Test]
    public function split_round_trips_random_valid_grapheme_sequences(): void
    {
        mt_srand(20260315);

        for ($case = 0; $case < 100; $case++) {
            $expected = $this->randomValidGraphemes(mt_rand(0, 20));
            $text = implode('', $expected);

            $this->assertSame(
                $expected,
                Grapheme::split($text),
                "Full split fuzz case {$case} failed."
            );
        }
    }

    #[Test]
    public function split_chunk_matches_split_for_random_valid_utf8_streams(): void
    {
        mt_srand(20260316);

        for ($case = 0; $case < 100; $case++) {
            $expected = $this->randomValidGraphemes(mt_rand(0, 20));
            $text = implode('', $expected);
            $chunks = $this->randomByteChunks($text);

            $this->assertSame(
                Grapheme::split($text),
                $this->streamChunks($chunks),
                "Streaming fuzz case {$case} failed."
            );
        }
    }

    #[Test]
    public function split_and_split_chunk_preserve_arbitrary_bytes_without_throwing(): void
    {
        mt_srand(20260317);
        $invalidParts = ["\x80", "\xBF", "\xC0", "\xC1", "\xF5", "\xFF"];

        for ($case = 0; $case < 100; $case++) {
            $parts = [];
            $partCount = mt_rand(0, 20);

            for ($index = 0; $index < $partCount; $index++) {
                if (mt_rand(0, 1) === 0) {
                    $parts[] = $invalidParts[array_rand($invalidParts)];
                } else {
                    $parts[] = $this->randomValidGraphemes(1)[0];
                }
            }

            $text = implode('', $parts);
            $split = Grapheme::split($text);

            $this->assertSame($text, implode('', $split), "Byte preservation fuzz case {$case} failed.");
            $this->assertSame($split, $this->streamChunks($this->randomByteChunks($text)), "Streaming byte fuzz case {$case} failed.");
        }
    }

    /**
     * @return list<string>
     */
    private function randomValidGraphemes(int $count): array
    {
        $corpus = [
            'A',
            '文',
            "e\u{0301}",
            "\u{2764}\u{FE0F}",
            '👨‍👩‍👧‍👦',
            '🇺🇸',
            '👍🏻',
            "\u{200B}",
            'क',
            '☎️',
            'あ',
            'ß',
        ];

        $graphemes = [];

        for ($index = 0; $index < $count; $index++) {
            $graphemes[] = $corpus[array_rand($corpus)];
        }

        return $graphemes;
    }

    /**
     * @return list<string>
     */
    private function randomByteChunks(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $offset = 0;
        $length = strlen($text);

        while ($offset < $length) {
            $size = mt_rand(1, 4);
            $chunks[] = substr($text, $offset, $size);
            $offset += $size;
        }

        return $chunks;
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
