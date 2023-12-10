<?php

/**
 * @see https://adventofcode.com/2023/day/10
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

    $grid = array_map('str_split', $lines);

    function findStart(array $grid): array
    {
        foreach ($grid as $y => $row) {
            foreach ($row as $x => $cell) {
                if ($cell === 'S') {
                    return [$x, $y];
                }
            }
        }
        throw new RuntimeException('No start found');
    }
    $start = findStart($grid);

    function traversePipe(array $start, array $grid): array
    {
        $visited = [];
        $queue = new SplQueue();
        $queue->enqueue($start);

        while (!$queue->isEmpty()) {
            $position = $queue->dequeue();
            [$x, $y] = $position;
            $key = "{$x},{$y}";

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $directions = findConnectingDirections($position, $grid);
            foreach ($directions as $direction) {
                switch ($direction) {
                    case 'N':
                        $queue->enqueue([$x, $y - 1]);
                        break;
                    case 'S':
                        $queue->enqueue([$x, $y + 1]);
                        break;
                    case 'E':
                        $queue->enqueue([$x + 1, $y]);
                        break;
                    case 'W':
                        $queue->enqueue([$x - 1, $y]);
                        break;
                }
            }
        }

        return $visited;
    }

    function findConnectingDirections(array $position, array $grid): array
    {
        /**
         * The pipes are arranged in a two-dimensional grid of tiles:
         *  | is a vertical pipe connecting north and south.
         *  - is a horizontal pipe connecting east and west.
         *  L is a 90-degree bend connecting north and east.
         *  J is a 90-degree bend connecting north and west.
         *  7 is a 90-degree bend connecting south and west.
         *  F is a 90-degree bend connecting south and east.
         *  . is ground; there is no pipe in this tile.
         *  S is the starting position of the animal; there is a pipe on this tile, but your sketch doesn't show what shape the pipe has.
         */
        $pipeTypes = [
            '|' => ['N', 'S'],
            '-' => ['E', 'W'],
            'L' => ['N', 'E'],
            'J' => ['N', 'W'],
            '7' => ['S', 'W'],
            'F' => ['S', 'E'],
            '.' => [],
        ];

        [$x, $y] = $position;
        $cell = $grid[$y][$x];
        $directions = $pipeTypes[$cell] ?? null;

        if ($directions === null) {
            // Also $cell === 'S'

            $adjacentCells = [
                'N' => $grid[$y - 1][$x] ?? null,
                'S' => $grid[$y + 1][$x] ?? null,
                'E' => $grid[$y][$x + 1] ?? null,
                'W' => $grid[$y][$x - 1] ?? null,
            ];
            foreach ($adjacentCells as $fromDirection => $adjacentCell) {
                foreach ($pipeTypes[$adjacentCell] ?? [] as $toDirection) {
                    switch ($fromDirection) {
                        case 'N':
                            if ($toDirection === 'S') {
                                $directions[] = $fromDirection;
                            }
                            break;
                        case 'S':
                            if ($toDirection === 'N') {
                                $directions[] = $fromDirection;
                            }
                            break;
                        case 'E':
                            if ($toDirection === 'W') {
                                $directions[] = $fromDirection;
                            }
                            break;
                        case 'W':
                            if ($toDirection === 'E') {
                                $directions[] = $fromDirection;
                            }
                            break;
                    }
                }
            }
        }

        return $directions;
    }

    function measureEnclosedGroundArea(array $pipePath, array $grid): int
    {
        $count = 0;
        foreach ($grid as $y => $row) {
            foreach ($row as $x => $cell) {
                if (!isset($pipePath["{$x},{$y}"]) && applyJordanCurveTheorem([$x, $y], $pipePath, $grid)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    function applyJordanCurveTheorem(array $position, array $pipePath, array $grid): bool
    {
        $intersections = 0;

        [$x, $y] = $position;

        // Going diagonally almost solves what to do about vertices
        $dx = $x - 1;
        $dy = $y - 1;
        while ($dx >= 0 && $dy >= 0) {
            if (isset($pipePath["{$dx},{$dy}"])) {
                $intersections++;

                // Literal corner cases
                $cell = $grid[$dy][$dx];
                if ($cell === 'S') {
                    $directions = findConnectingDirections([$dx, $dy], $grid);
                    if (count(array_intersect(['N', 'E'], $directions)) === 2) {
                        $cell = 'L';
                    } elseif (count(array_intersect(['S', 'W'], $directions)) === 2) {
                        $cell = '7';
                    }
                }
                if (in_array($cell, ['L', '7'])) {
                    $intersections++;
                }
            }

            $dx--;
            $dy--;
        }

        return ($intersections % 2 !== 0);
    }

    $t0 = microtime(true);

    $pipePath = traversePipe($start, $grid);
    $part1Answer = count($pipePath) / 2;

    $t1 = microtime(true);

    $part2Answer = measureEnclosedGroundArea($pipePath, $grid);

    $t2 = microtime(true);

    printf("Part 1: %d in %.6f sec\n", $part1Answer, $t1 - $t0);
    printf("Part 2: %d in %.6f sec\n", $part2Answer, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
