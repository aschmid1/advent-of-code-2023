<?php
/**
 * @see https://adventofcode.com/2023/day/21
 *
 * ======
 * Part 1
 * ======
 * The problem is easy enough to brute force with a recursive memoized search
 * for where the max step count is reached. It works, but takes ~1.4 seconds to
 * execute, which hurts our ability to reuse it for analysis in Part 2.
 *
 * A better solution is arrived at by observing the pattern of reachable points
 * for a given number steps when there are no obstacles. Below is a 1D example
 * which is easier to model and write out here.
 * ------------------
 * Steps 0: . . O . .
 * ------------------
 * Steps 1: . O . O .
 * ------------------
 * Steps 2: O . O . O
 * ------------------
 * We can observe that whether a point is reachable depends on the odd/even
 * parity between the distance from the starting point and max steps allowed.
 *
 * This pattern extrapolates nicely to 2D where parity is checked against the
 * Manhattan distances from the starting point.
 *
 * Steps 2:
 * ..O..
 * .O.O.
 * O.O.O
 * .O.O.
 * ..O..
 *
 * Finally, to account for rocks, we do a breadth-first flood fill (much faster
 * at ~0.018 seconds for the same map) to find which points are traversable
 * then apply the parity rule to those traversable points. Note that rocks do
 * not affect the parity rule, only which points are considered traversable.
 */

declare(strict_types=1);

namespace AdventOfCode2023\Day21;

const START = 'S';
const PLOT = '.';
const ROCK = '#';
const REACHED = 'O';
const DIRECTIONS = [
    'N' => ['x' => 0, 'y' => -1],
    'E' => ['x' => 1, 'y' => 0],
    'S' => ['x' => 0, 'y' => 1],
    'W' => ['x' => -1, 'y' => 0],
];

final class Point
{
    public static function get(int $x, int $y): self
    {
        static $cache = [];
        return $cache[$x][$y] ??= new self($x, $y);
    }

    private function __construct(
        private int $x,
        private int $y,
    ) {
    }

    public function distanceTo(self $point): int
    {
        return (int)(abs($this->x - $point->x) + abs($this->y - $point->y));
    }

    /**
     * @return Point[]
     */
    public function neighbours(int $delta = 1): array
    {
        return array_map(fn (array $d): self => self::get($this->x + $delta * (int)$d['x'], $this->y + $delta * (int)$d['y']), DIRECTIONS);
    }

    public function x(): int
    {
        return $this->x;
    }

    public function y(): int
    {
        return $this->y;
    }

    public function __toString(): string
    {
        return sprintf('(%d,%d)', $this->x, $this->y);
    }
}

function findReachedPoints(array $grid, int $maxRadius, Point $start): array
{
    $queue = new \SplQueue();
    $queue->enqueue(['point' => $start, 'step' => 0]);
    $traversed = [(string)$start => $start];
    $reached = ($maxRadius % 2 === 0) ? [(string)$start => $start] : [];

    while (!$queue->isEmpty()) {
        /**
         * @var Point $point
         * @var int $step
         */
        ['point' => $point, 'step' => $step] = $queue->dequeue();

        foreach ($point->neighbours() as $next) {
            if (
                ($grid[$next->x()][$next->y()] ?? null) === PLOT
                && !isset($traversed[(string)$next])
                && $step < $maxRadius
            ) {
                $traversed[(string)$next] = $next;
                if ($next->distanceTo($start) % 2 === $maxRadius % 2) {
                    $reached[(string)$next] = $next;
                }
                $queue->enqueue(['point' => $next, 'step' => $step + 1]);
            }
        }
    }

    return $reached;
}

/**
 * @param array $grid
 * @param int $maxRadius
 * @param Point $point
 * @param int $step
 * @param array $memo
 * @return array<string, Point> $reached
 * @deprecated Use findReachedPoints() instead. This is kept only for posterity.
 */
function recursiveSearch(array $grid, int $maxSteps, Point $point, int $step = 0, array &$memo = []): array
{
    if ($step >= $maxSteps) {
        return [(string)$point => REACHED];
    }

    $reached = [];

    foreach ($point->neighbours() as $next) {
        if (($grid[$next->x()][$next->y()] ?? null) === PLOT) {
            $reached += ($memo[(string)$next][$step + 1] ??= recursiveSearch($grid, $maxSteps, $next, $step + 1, $memo));
        }
    }

    return $reached;
}

(function (string $fileName) {
    $file = trim(file_get_contents($fileName));
    $grid = transpose(array_map('str_split', explode(PHP_EOL, $file)));
    $start = findStart($grid);
    $grid[$start->x()][$start->y()] = PLOT;
    debug(fn () => json_encode(array_map('implode', transpose($grid)), JSON_PRETTY_PRINT), 3);

    $maxSteps = match ($fileName) {
        'input.txt' => 64,
        'example1.txt' => 6,
        default => (int)floor(count($grid) / 2),
    };

    runPart(1, function () use ($grid, $maxSteps, $start): int {
        $reachedCells = findReachedPoints($grid, $maxSteps, $start);
        if (isDebugLevel(3)) {
            $overlay = array_reduce($reachedCells, function (array $overlay, Point $point): array {
                $overlay[$point->x()][$point->y()] = REACHED;
                return $overlay;
            }, $grid);
            debug(fn () => json_encode(array_map('implode', transpose($overlay)), JSON_PRETTY_PRINT), 3);
        }

        return count($reachedCells);
    });

    debug(fn () => str_repeat('-', 80));

    runPart(2, function () use ($grid, $maxSteps, $start): int {
        return -1;
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

function findStart(array $grid): Point
{
    foreach ($grid as $x => $column) {
        foreach ($column as $y => $cell) {
            if ($cell === START) {
                return Point::get($x, $y);
            }
        }
    }
    throw new \RuntimeException('Start not found');
}

function transpose(array $grid): array
{
    return array_map(null, ...$grid);
}

function runPart(int $part, callable $getAnswer): void
{
    $t0 = microtime(true);
    $answer = $getAnswer();
    $t1 = microtime(true);
    printf('Part %d: %s in %.6f sec' . PHP_EOL, $part, $answer, $t1 - $t0);
}

function debug(callable $getMessage, int $level = 1): void
{
    if (isDebugLevel($level)) {
        echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
    }
}

function isDebugLevel(int $level = 1): bool
{
    return ($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level);
}
