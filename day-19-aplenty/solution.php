<?php
/**
 * @see https://adventofcode.com/2023/day/19
 */

declare(strict_types=1);

namespace AdventOfCode2023\Day19;

const ACCEPTED = 'A';
const REJECTED = 'R';
const CATEGORIES = ['x', 'm', 'a', 's'];

/**
 * Value object representing a single workflow rule.
 */
final class Rule
{
    public static function fromString(string $ruleString): self
    {
        if ((strpos($ruleString, ':') === false)) {
            return new static(null, $ruleString);
        }

        [$conditionString, $destination] = explode(':', $ruleString, 2);
        $condition = RuleCondition::fromString($conditionString);

        return new static($condition, $destination);
    }

    public function __construct(
        private ?RuleCondition $condition,
        private string $destination,
    ) {
    }

    public function isSatisfiedBy(array $part): bool
    {
        return $this->condition === null || $this->condition->isSatisfiedBy($part);
    }

    public function condition(): ?RuleCondition
    {
        return $this->condition;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->condition, $this->destination);
    }
}

/**
 * Value object representing a single rule condition.
 */
final class RuleCondition
{
    public static function fromString(string $conditionString): self
    {
        preg_match('/^(?<category>[xmas]+)(?<operator>[<>])(?<value>\d+)$/', $conditionString, $matches);

        return new static($matches['category'], $matches['operator'], (int)$matches['value']);
    }

    public function __construct(
        private string $category,
        private string $operator,
        private int $value,
    ) {
    }

    public function isSatisfiedBy(array $part): bool
    {
        $partValue = $part[$this->category] ?? null;
        if ($partValue === null) {
            return false;
        }

        return match ($this->operator) {
            '<' => ($partValue < $this->value),
            '>' => ($partValue > $this->value),
        };
    }

    public function category(): string
    {
        return $this->category;
    }

    public function operator(): string
    {
        return $this->operator;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return sprintf('%s%s%d', $this->category, $this->operator, $this->value);
    }
}

function filterAcceptedParts(array $parts, array $workflows): array
{
    $acceptedParts = [];

    foreach ($parts as $part) {
        $partWorkflows = ['in'];
        for ($name = 'in'; !in_array($name, [ACCEPTED, REJECTED], true); $name = (string)$partWorkflows[array_key_last($partWorkflows)]) {
            foreach ($workflows[$name] as $rule) {
                if ($rule->isSatisfiedBy($part)) {
                    $partWorkflows[] = $rule->destination();
                    break;
                }
            }
        }
        if ($name === ACCEPTED) {
            $acceptedParts[] = $part;
        }
    }

    return $acceptedParts;
}

/**
 * @param string $name
 * @param array<string, array{min: int, max: int}> $categoryBounds
 * @param array<string, Rule[]> $workflows
 * @param array<string> $names for debugging
 * @return int
 */
function calculateCombinations(string $name, array $categoryBounds, array $workflows, array $names = []): int
{
    $names[] = $name;

    if ($name === REJECTED) {
        return 0;
    }
    if ($name === ACCEPTED) {
        $categoryIntervals = array_map(fn (array $bounds): int => max($bounds['max'] + 1 - $bounds['min'], 0), $categoryBounds);
        $intervalProduct = array_product($categoryIntervals);

        debug(fn () => sprintf(
            "%s\n\t%s\n\t=> %d",
            implode(' -> ', $names),
            json_encode(array_map('array_values', $categoryBounds)),
            $intervalProduct,
        ), 2);

        return $intervalProduct;
    }

    $sum = 0;

    foreach ($workflows[$name] as $rule) {
        $newCategoryBounds = $categoryBounds;

        if (($condition = $rule->condition())) {
            $category = $condition->category();
            $operator = $condition->operator();
            $value = $condition->value();

            $newBounds = $newCategoryBounds[$category];
            if ($operator === '<') {
                $newBounds['max'] = min($newBounds['max'], $value - 1);
            } else {
                $newBounds['min'] = max($newBounds['min'], $value + 1);
            }
            $newCategoryBounds[$category] = $newBounds;

            // Next workflow rule only occurs if the current condition was not met.
            $elseBounds = $categoryBounds[$category];
            if ($operator === '<') {
                $elseBounds['min'] = max($elseBounds['min'], $value);
            } else {
                $elseBounds['max'] = min($elseBounds['max'], $value);
            }
            $categoryBounds[$category] = $elseBounds;
        }

        $sum += calculateCombinations($rule->destination(), $newCategoryBounds, $workflows, $names);
    }

    return $sum;
}

(function (string $fileName) {
    $file = trim(file_get_contents($fileName));
    [$workflowsSection, $partsSection] = explode(PHP_EOL . PHP_EOL, $file, 2);
    preg_match_all('/^(?<name>[a-z]+)\{(?<rules>[^\}]+)\}$/m', $workflowsSection, $workflowMatches, PREG_SET_ORDER);
    preg_match_all('/^\{x=(?<x>\d+),m=(?<m>\d+),a=(?<a>\d+),s=(?<s>\d+)\}$/m', $partsSection, $partMatches, PREG_SET_ORDER);

    /** @var array<string, Rule[]> $workflows */
    $workflows = array_reduce($workflowMatches, function (array $map, array $matches): array {
        $name = (string)$matches['name'];
        $rules = array_map(
            fn (string $rule): Rule => Rule::fromString($rule),
            explode(',', $matches['rules'])
        );
        return $map + [$name => $rules];
    }, []);
    $parts = array_map(function (array $matches): array {
        return array_filter(
            $matches,
            fn ($key) => is_string($key),
            ARRAY_FILTER_USE_KEY
        );
    }, $partMatches);

    runPart(1, function () use ($workflows, $parts): int {
        return array_sum(array_map('array_sum', filterAcceptedParts($parts, $workflows)));
    });

    debug(fn () => str_repeat('-', 80));

    runPart(2, function () use ($workflows): int {
        $categoryBounds = array_fill_keys(CATEGORIES, ['min' => 1, 'max' => 4000]);

        return calculateCombinations('in', $categoryBounds, $workflows);
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
