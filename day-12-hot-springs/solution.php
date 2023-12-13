<?php

/**
 * @see https://adventofcode.com/2023/day/12
 */
(function (string $fileName) {
    function debug(callable $getMessage, int $level = 1): void
    {
        if (($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level)) {
            echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
        }
    }
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    $parsedRows = array_map(function (string $line): array {
        [$symbols, $blockSizes] = explode(' ', $line, 2);

        // Consectutive dots have no effect on arrangements, so collapse them to a single dot
        $symbols = preg_replace('/\.+/', '.', $symbols);
        $blockSizes = array_map('intval', explode(',', $blockSizes));

        return compact('symbols', 'blockSizes');
    }, $lines);

    debug(fn () => json_encode($parsedRows, JSON_PRETTY_PRINT), 3);

    $unfoldedRows = array_map(function (array $row): array {
        $symbols = implode('?', array_fill(0, 5, $row['symbols']));
        $blockSizes = array_merge(...array_fill(0, 5, $row['blockSizes']));

        return compact('symbols', 'blockSizes');
    }, $parsedRows);

    debug(fn () => json_encode($unfoldedRows, JSON_PRETTY_PRINT), 3);

    function recursivePermutations(string $symbols, array $blockSizes, int $index = 0, int $blockIndex = 0, array &$memo = []): int
    {
        if ($index === strlen($symbols)) {
            debug(fn () => '--- end of symbols', 3);
            return ($blockIndex === count($blockSizes)) ? 1 : 0;
        }

        $blockSize = (int)($blockSizes[$blockIndex] ?? 0);
        if (!$blockSize) {
            debug(fn () => '--- no more blocks', 3);
            $lastHashIndex = strrpos($symbols, '#');

            return ($lastHashIndex === false || $lastHashIndex < $index) ? 1 : 0;
        }

        $chooseDot = function () use ($symbols, $blockSizes, $index, $blockIndex, &$memo): int {
            $key = implode(',', [$index, $blockIndex, '.']);
            $length = 1;

            debug(fn () => json_encode([
                'key' => $key,
                'length' => $length,
                'symbols' => substr($symbols, 0, $index) . '>' . substr($symbols, $index, $length) . '<' . substr($symbols, $index + $length),
                'blockSizes' => implode(',', array_slice($blockSizes, 0, $blockIndex)) . '>' . implode(',', array_slice($blockSizes, $blockIndex)),
            ], JSON_PRETTY_PRINT), 3);

            return $memo[$key] ??= recursivePermutations(
                $symbols,
                $blockSizes,
                $index + $length,
                $blockIndex,
                $memo
            );
        };
        $chooseBlock = function () use ($symbols, $blockSizes, $index, $blockIndex, &$memo, $blockSize): int {
            $key = implode(',', [$index, $blockIndex, '#']);
            $length = strspn($symbols, '#?', $index, $blockSize);

            debug(fn () => json_encode([
                'key' => $key,
                'length' => $length,
                'symbols' => substr($symbols, 0, $index) . '>' . substr($symbols, $index, $length) . '<' . substr($symbols, $index + $length),
                'blockSizes' => implode(',', array_slice($blockSizes, 0, $blockIndex)) . '>' . implode(',', array_slice($blockSizes, $blockIndex)),
            ], JSON_PRETTY_PRINT), 3);

            $nextSymbol = $symbols[$index + $length] ?? '';
            if ($length !== $blockSize || $nextSymbol === '#') {
                debug(fn () => '--- invalid block', 3);

                return 0;
            }

            return $memo[$key] ??= recursivePermutations(
                $symbols,
                $blockSizes,
                $index + $length + strlen($nextSymbol),
                $blockIndex + 1,
                $memo
            );
        };

        switch ($symbols[$index]) {
            case '.':
                return $chooseDot();
            case '#':
                return $chooseBlock();
            case '?':
                return $chooseBlock() + $chooseDot();
        }

        throw new RuntimeException('Invalid symbol');
    }

    $p1t0 = microtime(true);

    $permutationCounts = array_map(function ($row) {
        return recursivePermutations((string)$row['symbols'], (array)$row['blockSizes']);
    }, $parsedRows);
    $part1Answer = array_sum($permutationCounts);

    $p1t1 = microtime(true);

    debug(fn () => print_r(array_map(fn ($count, $line) => $count . "\t" . $line, $permutationCounts, $lines), true), 2);
    printf("Part 1: %d in %.6f sec\n", $part1Answer, $p1t1 - $p1t0);
    debug(fn () => str_repeat('-', 80), 1);

    $p2t0 = microtime(true);

    $unfoldedCounts = array_map(function ($row) {
        return recursivePermutations((string)$row['symbols'], (array)$row['blockSizes']);
    }, $unfoldedRows);
    $part2Answer = array_sum($unfoldedCounts);

    $p2t1 = microtime(true);

    debug(fn () => print_r(array_map(fn ($count, $line) => $count . "\t" . $line, $unfoldedCounts, $lines), true), 2);
    printf("Part 2: %d in %.6f sec\n", $part2Answer, $p2t1 - $p2t0);

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
