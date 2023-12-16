<?php

namespace AdventOfCode2023\Day14;

const BLOCK = '#';
const SPACE = '.';
const ROCK = 'O';

const NORTH = 'north';
const SOUTH = 'south';
const EAST = 'east';
const WEST = 'west';

/**
 * @see https://adventofcode.com/2023/day/14
 */
(function (string $fileName) {
    $file = file_get_contents($fileName);
    $grid = explode(PHP_EOL, trim($file));
    debug(fn () => json_encode(['grid' => $grid], JSON_PRETTY_PRINT), 3);
    debug(fn () => json_encode(['transposedGrid' => transpose($grid)], JSON_PRETTY_PRINT), 4);

    runPart(1, function () use ($grid): int {
        $tiltedGrid = tiltGrid($grid, direction: NORTH);
        debug(fn () => json_encode(['tiltedGrid' => $tiltedGrid], JSON_PRETTY_PRINT), 3);

        return weighGrid($tiltedGrid);
    });

    runPart(2, function () use ($grid): int {
        $spinnedGrid = spinCycles($grid, numberOfCycles: 1000000000);
        debug(fn () => json_encode(['spinnedGrid' => $spinnedGrid], JSON_PRETTY_PRINT), 3);

        return weighGrid($spinnedGrid);
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

function spinCycles(array $grid, int $numberOfCycles): array
{
    $cycleCache = [];

    for ($cycle = 1; $cycle <= $numberOfCycles; $cycle++) {
        $key = sha1(implode($grid));
        if (isset($cycleCache[$key])) {
            ['grid' => $grid, 'base' => $base, 'previous' => $previous] = $cycleCache[$key];
            $period = ($cycleCache[$key]['period'] ??= $cycle - $previous);

            debug(fn () => sprintf('Cycle %d cache hit: %s', $cycle, json_encode($cycleCache[$key], JSON_PRETTY_PRINT)), 2);

            if (($numberOfCycles - $base) % $period === 0) {
                return $grid;
            }

            $cycleCache[$key]['previous'] = $cycle;
        } else {
            $grid = spinGrid($grid);
            $cycleCache[$key] = [
                'key' => $key,
                'grid' => $grid,
                'base' => $cycle,
                'previous' => $cycle,
            ];
        }

        debug(fn () => json_encode(['cycle' => $cycle, 'grid' => $grid], JSON_PRETTY_PRINT), 4);
    }

    return $grid;
}

function spinGrid(array $grid): array
{
    foreach ([NORTH, WEST, SOUTH, EAST] as $direction) {
        $grid = tiltGrid($grid, $direction);
    }

    return $grid;
}

function tiltGrid(array $grid, string $direction): array
{
    return match ($direction) {
        NORTH => transpose(rollRocks(transpose($grid), backwards: true)),
        SOUTH => transpose(rollRocks(transpose($grid), backwards: false)),
        EAST => rollRocks($grid, backwards: false),
        WEST => rollRocks($grid, backwards: true),
    };
}

function rollRocks(array $transposedGrid, bool $backwards): array
{
    return array_map(function ($row) use ($backwards): string {
        $rollablePaths = explode(BLOCK, $row);

        $rolledPaths = array_map(function ($path) use ($backwards): string {
            $numberOfRocks = substr_count($path, ROCK);
            $numberOfSpaces = strlen($path) - $numberOfRocks;

            $rocks = str_repeat(ROCK, $numberOfRocks);
            $spaces = str_repeat(SPACE, $numberOfSpaces);

            return ($backwards)
                ? ($rocks . $spaces)
                : ($spaces . $rocks);
        }, $rollablePaths);

        return implode(BLOCK, $rolledPaths);
    }, $transposedGrid);
}

function weighGrid(array $grid): int
{
    return array_sum(array_map(fn ($col) => weighColumn($col), transpose($grid)));
}

function weighColumn(string $column): int
{
    $rockIndexes = array_keys(str_split(strrev($column)), ROCK, true);

    return array_sum($rockIndexes) + count($rockIndexes);
}

function transpose(array $array): array
{
    return array_map('implode', array_map(null, ...array_map('str_split', $array)));
}

function runPart(int $part, callable $getAnswer): void
{
    $t0 = microtime(true);
    $answer = $getAnswer();
    $t1 = microtime(true);
    printf('Part %d: %d in %.6f sec' . PHP_EOL, $part, $answer, $t1 - $t0);
}

function debug(callable $getMessage, int $level = 1): void
{
    if (($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level)) {
        echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
    }
}
