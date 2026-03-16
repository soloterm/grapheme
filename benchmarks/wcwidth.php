<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use SoloTerm\Grapheme\Grapheme;

$iterations = 10000;
$testChars = [
    'a', 'Z', ' ', '@',
    'é', 'ñ', 'ü',
    '文', '字', 'あ', '한',
    '😀', '🚀', '👍',
    '👨‍👩‍👧‍👦',
    "\u{200B}",
];

foreach ($testChars as $char) {
    Grapheme::wcwidth($char);
}

$start = microtime(true);
for ($iteration = 0; $iteration < $iterations; $iteration++) {
    foreach ($testChars as $char) {
        Grapheme::wcwidth($char);
    }
}
$cachedTime = (microtime(true) - $start) * 1000;

Grapheme::clearCache();
$start = microtime(true);
for ($iteration = 0; $iteration < $iterations; $iteration++) {
    Grapheme::clearCache();

    foreach ($testChars as $char) {
        Grapheme::wcwidth($char);
    }
}
$uncachedTime = (microtime(true) - $start) * 1000;
$totalCalls = $iterations * count($testChars);

echo "Performance Benchmark ({$totalCalls} calls):\n";
echo '  Cached:   ' . round($cachedTime, 2) . ' ms (' . round($totalCalls / $cachedTime * 1000) . " calls/sec)\n";
echo '  Uncached: ' . round($uncachedTime, 2) . ' ms (' . round($totalCalls / $uncachedTime * 1000) . " calls/sec)\n";
echo '  Speedup:  ' . round($uncachedTime / $cachedTime, 1) . "x\n";
