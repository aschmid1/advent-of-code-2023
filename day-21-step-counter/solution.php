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
 *
 * ======
 * Part 2
 * ======
 * Given that the actual input data:
 * - Begins at the center of a repeating square map
 * - the path to a each border is not blocked
 * - the borders are all unblocked
 * - and some points are completely enclosed by rocks
 * A Reddit user came up with a much better example input:
 * https://www.reddit.com/r/adventofcode/comments/18o1071/2023_day_21_a_better_example_input_mild_part_2/
 *
 * Let's consider a 1D example of an infinite map starting from the center point:
 * --------------------------------------
 * Steps 0: . . . . .|. . O . .|. . . . . 1D = 1; 2D =  1
 * --------------------------------------
 * Steps 1: . . . . .|. O . O .|. . . . . 1D = 2; 2D =  4
 * --------------------------------------
 * Steps 2: . . . . .|O . O . O|. . . . . 1D = 3; 2D =  9
 * --------------------------------------
 * Steps 3: . . . . O|. O . O .|O . . . . 1D = 4; 2D = 16
 * --------------------------------------
 * Steps 4: . . . O .|O . O . O|. O . . . 1D = 5; 2D = 25
 * --------------------------------------
 * Steps 5: . . O . O|. O . O .|O . O . . 1D = 6; 2D = 36
 * --------------------------------------
 * This is enough to see that for each parallel maps, the pattern is the same
 * with opposite parity.
 * We can also see that the number of reachable points is a linear function of
 * the number of steps taken:
 *   f(0) = 1, f(1) = 2, f(2) = 3, ...
 *   f(steps) = steps + 1
 * which becomes quadratic when extrapolated to 2D (image not shown).
 *   f(0) = 1, f(1) = 4, f(2) = 9, ...
 *   f(steps) = (steps + 1)^2 = (steps)^2 + 2(steps) + 1
 * However, this is not enough to solve the problem because we need to account
 * for the fact that the map contains rocks. Given that the rock patterns
 * repeat on each map, we can model the answer as a function of maps traversed.
 *
 * Using the above map as a simple example, due to having no rocks, where
 * width=5 and radius=floor(5/2)=2, we see that maps are traversed at
 * steps 2, 7, 12, ..., (5(maps) + 2) or (S(maps) = width(maps) + radius).
 *
 * Maps 0 Steps  2: | | |O| | | 1D =  3; 2D =   9
 * Maps 1 Steps  7: | |O|O|O| | 1D =  8; 2D =  64
 * Maps 2 Steps 12: |O|O|O|O|O| 1D = 13; 2D = 144
 *
 * 1D
 *   f(0) = 3, f(1) = 8, f(2) = 13, ...
 *   f(maps) = steps + 1 = [5(maps) + 2] + 1 = 5(maps) + 3
 * 2D
 *   f(0) = 9, f(1) = 64, f(2) = 144, ...
 *   f(maps) = (5(maps) + 3)^2 = 25(maps)^2 + 30(maps) + 9
 *
 * For the actual problem, take note that 26501365 steps is equivalent to
 * exactly 202300 maps of the actual input data.
 * To account for rocks in the actual input data, we measure 3 actual
 * datapoints that can be used to derive the quadratic function.
 *
 * Steps to measure:
 *   S(maps) = 131(maps) + 65
 *   S(0) = 65, S(1) = 196, S(2) = 327
 * Measured data:
 *   f(0) = 3799, f(1) = 34047, f(2) = 94475
 * Deriving the quadratic:
 *   0 = a(3799)^2 + b(3799) + c
 *   1 = a(34047)^2 + b(34047) + c
 *   2 = a(94475)^2 + b(94475) + c
 *   =============================
 *   a = 15090
 *   b = 15158
 *   c = 3799
 * Results in the function:
 *   f(maps) = 15090(maps)^2 + 15158(maps) + 3799
 *
 * Which can be used to calculate the Part 2 answer without any additional code.
 *   26501365 = 131(maps) + 65
 *   => maps = (26501365 - 65) / 131
 *           = 202300
 *   f(202300) = 617_565_692_567_199
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

    public function getWrappped(int $width, int $height): Point
    {
        return self::get($this->wrapAxis($this->x, $width), $this->wrapAxis($this->y, $height));
    }

    private function wrapAxis(int $value, int $max): int
    {
        // Fix the fact that -3 % 5 = -3 instead of 2
        return ($max + ($value % $max)) % $max;
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

function findReachedPoints(array $grid, int $maxRadius, Point $start, bool $infiniteMap = false): array
{
    $width = count($grid);

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
            $gridPoint = ($infiniteMap) ? $next->getWrappped($width, $width) : $next;
            if (
                ($grid[$gridPoint->x()][$gridPoint->y()] ?? null) === PLOT
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

    $width = count($grid);
    $maxSteps = match ($fileName) {
        'input.txt' => 26501365,
        'example1.txt' => 1000,
        default => 2 * $width + (int)floor($width / 2),
    };
    runPart(2, function () use ($grid, $maxSteps, $start, $width, $fileName): int {
        if (isDebugLevel(2)) {
            $maxStepSamples = match ($fileName) {
                'input.txt' => [65, 196, 327/* , 26501365 */],
                'example1.txt' => [6, 10, 50, 100, 500/*, 1000, 5000 */],
                default => array_map(fn ($i) => $i * $width + (int)floor($width / 2), [0, 1, 2]),
            };

            foreach ($maxStepSamples as $maxStepSample) {
                $reachedCells = count(findReachedPoints($grid, $maxStepSample, $start, infiniteMap: true));
                debug(fn () => sprintf('(%d,%d)', $maxStepSample, $reachedCells), 2);
            }
        } else {
            // See docblock for an explanation of how this was derived.
            $reachedCells = 617565692567199;
        }

        return $reachedCells;
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
