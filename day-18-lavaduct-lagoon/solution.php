<?php
/**
 * @see https://adventofcode.com/2023/day/18
 *
 * This appears to be an application of the Shoelace formula: https://en.wikipedia.org/wiki/Shoelace_formula
 * but I imagine there could be a simplification based on the vertices all being orthogonal.
 */

declare(strict_types=1);

namespace AdventOfCode2023\Day18;

const UP = 'U';
const DOWN = 'D';
const LEFT = 'L';
const RIGHT = 'R';

/**
 * Value object representing a single step in the dig plan.
 */
final class DigPlanStep
{
    public static function get(string $direction, int $distance): self
    {
        static $cache = [];
        return $cache[$direction][$distance] ??= new self($direction, $distance);
    }

    private function __construct(
        private string $direction,
        private int $distance,
    ) {
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function distance(): int
    {
        return $this->distance;
    }

    public function __toString(): string
    {
        return sprintf('%s %d', $this->direction, $this->distance);
    }
}

/**
 * Value object representing a single coordinate.
 */
final class Coordinate
{
    public static function get(int $x, int $y): self
    {
        static $cache = [];
        return $cache[$x][$y] ??= new self($x, $y);
    }

    public static function getRelative(self $from, string $direction, int $distance): self
    {
        $x = $from->x();
        $y = $from->y();

        switch ($direction) {
            case UP:
                $y += $distance;
                break;
            case DOWN:
                $y -= $distance;
                break;
            case LEFT:
                $x -= $distance;
                break;
            case RIGHT:
                $x += $distance;
                break;
        }

        return static::get($x, $y);
    }

    private function __construct(
        private int $x,
        private int $y,
    ) {
    }

    public function crossProduct(self $other): int
    {
        return $this->x * $other->y - $this->y * $other->x;
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
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}

/**
 * Use the Shoelace formula to calculate the area of the dig plan polygon.
 *
 * Note that the Shoelace formula assumes each point to be the center of the square.
 * Therefore, we need to add the half the perimeter plus 1.
 *
 * @see https://en.wikipedia.org/wiki/Shoelace_formula
 *
 * @param DigPlanStep[] $digPlan
 */
function calculateDigArea(array $digPlan): int
{
    $perimeter = 0;
    $area = 0;

    $from = Coordinate::get(0, 0);
    foreach ($digPlan as $step) {
        $to = Coordinate::getRelative($from, $step->direction(), $step->distance());
        $perimeter += $step->distance();
        $area += $from->crossProduct($to);
        $from = $to;
    }

    return (int)((abs($area) + $perimeter) / 2) + 1;
}

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $lines = explode(PHP_EOL, trim($file));

    runPart(1, function () use ($lines): int {
        $digPlan = array_map(function (string $line): DigPlanStep {
            [$direction, $distance, $hex] = explode(' ', $line, 3);

            return DigPlanStep::get($direction, (int)$distance);
        }, $lines);

        return calculateDigArea($digPlan);
    });

    debug(fn () => str_repeat('-', 80));

    runPart(2, function () use ($lines): int {
        $digPlan = array_map(function (string $line): DigPlanStep {
            preg_match('/\(#(?<distanceHex>[0-9a-f]+)(?<directionHex>[0-3])\)$/', $line, $matches);

            $distance = hexdec($matches['distanceHex']);
            $direction = match ($matches['directionHex']) {
                '0' => RIGHT,
                '1' => DOWN,
                '2' => LEFT,
                '3' => UP,
                default => throw new \RuntimeException('Invalid direction'),
            };

            return DigPlanStep::get($direction, (int)$distance);
        }, $lines);

        return calculateDigArea($digPlan);
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
