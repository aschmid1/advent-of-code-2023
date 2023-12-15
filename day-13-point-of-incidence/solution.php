<?php

/**
 * @see https://adventofcode.com/2023/day/13
 */
(function (string $fileName) {
    function debug(callable $getMessage, int $level = 1): void
    {
        if (($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level)) {
            echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
        }
    }
    $file = file_get_contents($fileName);

    function transpose(array $array): array
    {
        $array = array_map('str_split', $array);
        $array = array_map(null, ...$array);
        return array_map('implode', $array);
    }

    function findTwoWayReflectionIndex(array $lines, int $smudges): int
    {
        // Vertical
        $indexes = findOneWayReflectionIndex($lines, $smudges);
        if (isset($indexes[$smudges])) {
            return 100 * $indexes[$smudges];
        }

        $lines = transpose($lines);

        // Horizontal
        $indexes = findOneWayReflectionIndex($lines, $smudges);
        if (isset($indexes[$smudges])) {
            return $indexes[$smudges];
        }

        throw new RuntimeException('No reflection found with indexes: ' . json_encode($indexes, JSON_PRETTY_PRINT) . ' for: ' . json_encode($lines, JSON_PRETTY_PRINT));
    }

    function findOneWayReflectionIndex(array $lines, int $smudges): array
    {
        $indexes = [];
        $potentialIndexes = findPotentialReflectionIndexes($lines, $smudges);
        for ($s = $smudges; $s >= 0; $s--) {
            foreach ($potentialIndexes as $index => $diffs) {
                if (verifyReflectionIndex($lines, $index, $diffs, $s)) {
                    $indexes[$s] = ($index + 1);
                }
            }
        }
        return $indexes;
    }

    function findPotentialReflectionIndexes(array $lines, int $smudges): array
    {
        $indexes = [];
        for ($i = 0; $i < count($lines) - 1; $i++) {
            $diffs = countLineDiffs($lines[$i], $lines[$i + 1], $smudges);
            if ($diffs <= $smudges) {
                $indexes[$i] = $diffs;
            }
        }
        return $indexes;
    }

    function verifyReflectionIndex(array $lines, int $index, int $diffs, int $smudges): bool
    {
        for ($i = 1; ($index - $i) >= 0 && ($index + 1 + $i) < count($lines); $i++) {
            $diffs += countLineDiffs($lines[$index - $i], $lines[$index + 1 + $i], $smudges);
            if ($diffs > $smudges) {
                return false;
            }
        }
        return $diffs === $smudges;
    }

    function countLineDiffs(string $line1, string $line2, int $smudges): int
    {
        $diff = 0;
        for ($i = 0; $i < strlen($line1); $i++) {
            if ($line1[$i] !== $line2[$i]) {
                $diff++;
                if ($diff > $smudges) {
                    return $diff;
                }
            }
        }
        return $diff;
    }

    $sections = array_filter(array_map(
        fn (string $section): array => array_filter(array_map('trim', explode(PHP_EOL, $section))),
        explode(PHP_EOL . PHP_EOL, $file)
    ));
    debug(fn () => sprintf('Sections: %s', json_encode($sections, JSON_PRETTY_PRINT)), 3);

    $p1t0 = microtime(true);

    $results = array_map(function (array $lines): int {
        return findTwoWayReflectionIndex($lines, smudges: 0);
    }, $sections);

    $part1Answer = array_sum($results);

    $p1t1 = microtime(true);

    printf("Part 1: %d in %.6f sec\n", $part1Answer, $p1t1 - $p1t0);

    $p2t0 = microtime(true);

    $results = array_map(function (array $lines): int {
        return findTwoWayReflectionIndex($lines, smudges: 1);
    }, $sections);

    $part2Answer = array_sum($results);

    $p2t1 = microtime(true);

    printf("Part 2: %d in %.6f sec\n", $part2Answer, $p2t1 - $p2t0);

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
