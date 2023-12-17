<?php
/**
 * @see https://adventofcode.com/2023/day/16
 */

namespace AdventOfCode2023\Day16;

const BEAM_N = '^';
const BEAM_S = 'v';
const BEAM_E = '>';
const BEAM_W = '<';

/**
 * Traverses breadth-first because It might allow for earlier pruning of revisited paths.
 *
 * @param array $grid
 * @param array $start
 * @return array Visited cells
 */
function breadthFirstTraversal(array $grid, array $start = [0, 0, BEAM_E]): array {
    $visited = [];

    $queue = new \SplQueue();
    $queue->enqueue($start);

    while (!$queue->isEmpty()) {
        [$x, $y, $direction] = $queue->dequeue();

        if (!isset($grid[$y][$x])) {
            continue;
        }
        $cell = $grid[$y][$x];

        if (isset($visited["$x,$y"][$direction])) {
            continue;
        }
        $visited["$x,$y"][$direction] = true;

        $nextBeam = function(string $direction) use ($x, $y): array {
            switch ($direction) {
                case BEAM_N:
                    return [$x, $y - 1, BEAM_N];
                case BEAM_S:
                    return [$x, $y + 1, BEAM_S];
                case BEAM_E:
                    return [$x + 1, $y, BEAM_E];
                case BEAM_W:
                    return [$x - 1, $y, BEAM_W];
            }
            throw new \RuntimeException(sprintf('Unknown direction "%s" at %d,%d', $direction, $x, $y));
        };

        switch ($cell) {
            case '.':
                $queue->enqueue($nextBeam($direction));
                break;
            case '\\':
                switch ($direction) {
                    case BEAM_N:
                        $queue->enqueue($nextBeam(BEAM_W));
                        break;
                    case BEAM_S:
                        $queue->enqueue($nextBeam(BEAM_E));
                        break;
                    case BEAM_E:
                        $queue->enqueue($nextBeam(BEAM_S));
                        break;
                    case BEAM_W:
                        $queue->enqueue($nextBeam(BEAM_N));
                        break;
                }
                break;
            case '/':
                switch ($direction) {
                    case BEAM_N:
                        $queue->enqueue($nextBeam(BEAM_E));
                        break;
                    case BEAM_S:
                        $queue->enqueue($nextBeam(BEAM_W));
                        break;
                    case BEAM_E:
                        $queue->enqueue($nextBeam(BEAM_N));
                        break;
                    case BEAM_W:
                        $queue->enqueue($nextBeam(BEAM_S));
                        break;
                }
                break;
            case '|':
                switch ($direction) {
                    case BEAM_N:
                    case BEAM_S:
                        $queue->enqueue($nextBeam($direction));
                        break;
                    case BEAM_E:
                    case BEAM_W:
                        $queue->enqueue($nextBeam(BEAM_N));
                        $queue->enqueue($nextBeam(BEAM_S));
                        break;
                }
                break;
            case '-':
                switch ($direction) {
                    case BEAM_N:
                    case BEAM_S:
                        $queue->enqueue($nextBeam(BEAM_E));
                        $queue->enqueue($nextBeam(BEAM_W));
                        break;
                    case BEAM_E:
                    case BEAM_W:
                        $queue->enqueue($nextBeam($direction));
                        break;
                }
                break;
            default:
                throw new \RuntimeException(sprintf('Unknown cell type "%s" at %d,%d', $cell, $x, $y));
        }
    }

    return array_keys($visited);
}

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $grid = explode(PHP_EOL, trim($file));
    debug(fn () => print_r($grid, true), 4);
    $grid = array_map('str_split', $grid);

    runPart(1, function () use ($grid): int {
        return count(breadthFirstTraversal($grid));
    });

    runPart(2, function () use ($grid): int {
        $startingPoints = [];
        foreach ($grid as $y => $row) {
            $startingPoints[] = [0, $y, BEAM_E];
            $startingPoints[] = [count($row) - 1, $y, BEAM_W];
        }
        foreach ($grid[0] as $x => $cell) {
            $startingPoints[] = [$x, 0, BEAM_S];
            $startingPoints[] = [$x, count($grid) - 1, BEAM_N];
        }

        // I expected this to be a lot slower than it is
        $visitedCounts = [];
        foreach ($startingPoints as $startingPoint) {
            $visitedCounts[] = count(breadthFirstTraversal($grid, $startingPoint));
        }

        return max($visitedCounts);
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

function runPart(int $part, callable $getAnswer): void
{
    $t0 = microtime(true);
    $answer = $getAnswer();
    $t1 = microtime(true);
    printf('Part %d: %s in %.6f sec' . PHP_EOL, $part, $answer, $t1 - $t0);
}

function debug(callable $getMessage, int $level = 1): void
{
    if (($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level)) {
        echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
    }
}
