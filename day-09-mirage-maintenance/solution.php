<?php

/**
 * @see https://adventofcode.com/2023/day/9
 */
(function (string $fileName) {
    function debug(callable $getMessage): void
    {
        if ($_SERVER['DEBUG'] ?? false) {
            echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
        }
    }
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    $readingsTable = array_map(fn ($line) => array_map('intval', explode(' ', $line)), $lines);

    /**
     * @param int[] $values
     * @return int[]
     */
    function differentiate(array $values): array
    {
        $differences = [];
        for ($i = 0; $i < count($values) - 1; $i++) {
            $differences[] = $values[$i + 1] - $values[$i];
        }
        return $differences;
    }

    /**
     * @param int[] $readings
     * @return int
     */
    function predictNext(array $readings, bool $reverse = false): int
    {
        $stack = [];
        for ($diffs = $readings; array_filter($diffs); $diffs = differentiate($diffs)) {
            array_push($stack, $diffs);
        }

        $next = 0;
        while (($diffs = array_pop($stack))) {
            if (!$reverse) {
                $next += $diffs[array_key_last($diffs)];
            } else {
                $next = $diffs[array_key_first($diffs)] - $next;
            }
        }

        return $next;
    }

    $t0 = microtime(true);

    $part1Answer = array_sum(array_map('predictNext', $readingsTable));

    $t1 = microtime(true);

    $part2Answer = array_sum(array_map(fn ($readings) => predictNext($readings, true), $readingsTable));

    $t2 = microtime(true);

    printf("Part 1: %d in %.6f sec\n", $part1Answer, $t1 - $t0);
    printf("Part 2: %d in %.6f sec\n", $part2Answer, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
