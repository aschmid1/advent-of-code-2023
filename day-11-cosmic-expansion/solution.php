<?php

/**
 * @see https://adventofcode.com/2023/day/11
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

    $universe = array_map('str_split', $lines);

    function findEmptyRowsAndColumns(array $universe): array
    {
        $rowIsEmpty = [];
        $columnIsEmpty = [];

        foreach ($universe as $y => $row) {
            $rowIsEmpty[$y] ??= true;
            foreach ($row as $x => $cell) {
                $columnIsEmpty[$x] ??= true;
                if ($cell !== '.') {
                    $rowIsEmpty[$y] = false;
                    $columnIsEmpty[$x] = false;
                }
            }
        }

        return [array_keys($rowIsEmpty, true, true), array_keys($columnIsEmpty, true, true)];
    }

    function findGalaxies(array $universe): array
    {
        $galaxies = [];

        $galaxyId = 0;
        foreach ($universe as $y => $row) {
            foreach ($row as $x => $cell) {
                if ($cell === '#') {
                    $galaxies[++$galaxyId] = [$x, $y];
                }
            }
        }

        return $galaxies;
    }

    function measureExpandedGalacticDistances(array $universe, int $rate = 2): array
    {
        list($emptyRows, $emptyColumns) = findEmptyRowsAndColumns($universe);
        $galaxies = findGalaxies($universe);

        $galacticDistances = [];

        foreach ($galaxies as $id1 => $galaxy1) {
            foreach ($galaxies as $id2 => $galaxy2) {
                if ($id1 < $id2) {
                    list($x1, $y1) = $galaxy1;
                    list($x2, $y2) = $galaxy2;

                    $rowExpansion = 0;
                    foreach ($emptyRows as $emptyRow) {
                        if (
                            $y1 < $emptyRow && $emptyRow < $y2
                            || $y2 < $emptyRow && $emptyRow < $y1
                        ) {
                            $rowExpansion += $rate - 1;
                        }
                    }

                    $columnExpansion = 0;
                    foreach ($emptyColumns as $emptyColumn) {
                        if (
                            $x1 < $emptyColumn && $emptyColumn < $x2
                            || $x2 < $emptyColumn && $emptyColumn < $x1
                        ) {
                            $columnExpansion += $rate - 1;
                        }
                    }

                    $galacticDistances[$id1][$id2] = abs($x1 - $x2) + abs($y1 - $y2) + $rowExpansion + $columnExpansion;
                }
            }
        }

        return $galacticDistances;
    }

    $t0 = microtime(true);

    $galacticDistances = measureExpandedGalacticDistances($universe, 2);
    debug(fn () => print_r($galacticDistances, true));
    $part1Answer = array_sum(array_map('array_sum', $galacticDistances));

    $t1 = microtime(true);

    $galacticDistances = measureExpandedGalacticDistances($universe, 1000000);
    debug(fn () => print_r($galacticDistances, true));
    $part2Answer = array_sum(array_map('array_sum', $galacticDistances));

    $t2 = microtime(true);

    printf("Part 1: %d in %.6f sec\n", $part1Answer, $t1 - $t0);
    printf("Part 2: %d in %.6f sec\n", $part2Answer, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
