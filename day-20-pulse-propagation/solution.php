<?php
/**
 * @see https://adventofcode.com/2023/day/20
 *
 * Part 1
 * I found the following phrasing as too ambiguous:
 * > If a flip-flop module receives a high pulse, it is ignored and nothing
 * > happens.
 * It would have saved me time if they described it as terminating the pulse.
 *
 * Part 2
 * Similar to Day 8, where Reddit and I dislike it because it required analysis
 * of the structure of the actual input data to discover that it is composed of
 * multiple loops which must be cycled simultaneously (like the Day 8 problem).
 * So the problem becomes a matter of finding the LCM of those cycle lengths.
 *
 * Nice visualization of a particular Reddit user's input data set:
 * https://www.reddit.com/r/adventofcode/comments/18mypla/2023_day_20_input_data_plot/
 */

declare(strict_types=1);

namespace AdventOfCode2023\Day20;

const NONE = 'NONE';
const BUTTON = 'button';
const BROADCASTER = 'broadcaster';

enum Pulse: int
{
    case LOW = 0;
    case HIGH = 1;
}

enum ModuleType: string
{
    case NONE = '';
    case FLIP_FLOP = '%';
    case CONJUNCTION = '&';
}

/**
 * Value object representing a single module.
 */
final class Module
{
    /** @var Module[] */
    public array $inputs = [];
    /** @var Module[] */
    public array $outputs = [];

    private bool $isOn = false;
    /** @var array<string, Pulse> */
    private array $inputPulses = [];

    public function __construct(
        private ?ModuleType $type,
        private string $name,
    ) {
    }

    public function processPulse(Pulse $pulse, Module $from): ?Pulse
    {
        $this->inputPulses = $this->inputPulses();
        if (!isset($this->inputPulses[$from->name()])) {
            throw new \InvalidArgumentException(sprintf('Module %s does not have input %s', $this->name, $from->name()));
        }

        switch ($this->type) {
            case ModuleType::FLIP_FLOP:
                if ($pulse === PULSE::HIGH) {
                    return null;
                }

                $this->isOn = !$this->isOn;
                return $this->isOn ? Pulse::HIGH : Pulse::LOW;
            case ModuleType::CONJUNCTION:
                $this->inputPulses[$from->name()] = $pulse;
                $allPulsesAreHigh = !in_array(Pulse::LOW, $this->inputPulses, true);

                return ($allPulsesAreHigh) ? Pulse::LOW : Pulse::HIGH;
            default:
                return $pulse;
        }
    }

    public function reset(): void
    {
        $this->isOn = false;
        foreach ($this->inputPulses() as $inputName => $pulse) {
            $this->inputPulses[$inputName] = Pulse::LOW;
        }
    }

    public function type(): ?ModuleType
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function inputNames(): array
    {
        return array_map(fn (Module $module): string => $module->name(), $this->inputs);
    }

    /**
     * @return string[]
     */
    public function outputNames(): array
    {
        return array_map(fn (Module $module): string => $module->name(), $this->outputs);
    }

    public function isOn(): bool
    {
        return $this->isOn;
    }

    /**
     * @return Pulse[]
     */
    public function inputPulses(): array
    {
        if (array_keys($this->inputPulses) !== $this->inputNames()) {
            $inputPulses = [];
            foreach ($this->inputNames() as $inputName) {
                $inputPulses[$inputName] = $this->inputPulses[$inputName] ?? Pulse::LOW;
            }
            $this->inputPulses = $inputPulses;
        }

        return $this->inputPulses;
    }

    public function __toString(): string
    {
        $inputNames = ($this->inputs)
            ? implode(', ', $this->inputNames())
            : ($this->name === BROADCASTER ? BUTTON : NONE);
        $outputNames = ($this->outputs)
            ? implode(', ', $this->outputNames())
            : NONE;

        return sprintf('%s -> %s%s -> %s', $inputNames, $this->type, $this->name, $outputNames);
    }
}

/**
 * @param Module[] $modules
 * @param callable $readPulse function(Pulse $pulse, Module $input, Module $output): bool
 * @return array<string, int>
 */
function pressButton(array $modules, callable $readPulse): void
{
    $queue = new \SplQueue();
    $queue->enqueue(['input' => $modules[BUTTON], 'pulse' => Pulse::LOW, 'output' => $modules[BROADCASTER]]);

    while (!$queue->isEmpty()) {
        /**
         * @var Module $input
         * @var Pulse $pulse
         * @var Module $output
         */
        ['input' => $input, 'pulse' => $pulse, 'output' => $output] = $queue->dequeue();
        debug(fn () => sprintf('%s -%s-> %s', $input->name(), $pulse->name, $output->name()), 3);

        if ($readPulse($input, $pulse, $output)) {
            return;
        }

        $pulse = $output->processPulse($pulse, $input);
        if ($pulse === null) {
            continue;
        }

        foreach ($output->outputs as $next) {
            $queue->enqueue(['input' => $output, 'pulse' => $pulse, 'output' => $next]);
        }
    }
}

(function (string $fileName) {
    $file = trim(file_get_contents($fileName));

    $modules = extractModules($file);
    runPart(1, function () use ($modules): int {
        $presses = 1000;

        $countPulses = [];
        for ($i = 0; $i < $presses; $i++) {
            debug(fn () => sprintf('--- Press %d ---', $i + 1), 2);

            pressButton($modules, function (Module $input, Pulse $pulse, Module $output) use (&$countPulses): void {
                $countPulses[$pulse->name] = ($countPulses[$pulse->name] ?? 0) + 1;
            });

            debug(fn () => json_encode($countPulses), 2);
            debug(fn () => PHP_EOL, 2);
        }

        return array_product($countPulses);
    });

    debug(fn () => str_repeat('-', 80));

    foreach ($modules as $module) {
        $module->reset();
    }
    if ($fileName !== 'input.txt') {
        print 'Part 2: Must set the input file to input.txt' . PHP_EOL;
        return;
    }
    runPart(2, function () use ($modules): int {
        $topConjunctions = findTopConjunctionsLeadingTo('rx', $modules);
        $cycleLengths = array_fill_keys($topConjunctions, null);

        $break = false;
        for ($presses = 1; !$break; $presses++) {
            pressButton($modules, function (Module $input, Pulse $pulse, Module $output) use ($presses, &$cycleLengths, &$break): bool {
                if ($pulse === Pulse::LOW && $output->name() === 'rx') {
                    return ($break = true);
                }

                foreach ($cycleLengths as $name => $length) {
                    if ($length === null && $name === $input->name() && $pulse === Pulse::LOW) {
                        $cycleLengths[$name] = $presses;
                    }
                }

                return ($break = !in_array(null, $cycleLengths, true));
            });
        }

        debug(fn () => json_encode($cycleLengths));

        // LCM wasn't strictly necessary because they all happened to be prime numbers
        //$lcm = array_reduce($cycleLengths, fn ($lcm, $cycleLength) => $lcm * $cycleLength / gcd($lcm, $cycleLength), 1);
        $lcm = array_product($cycleLengths);

        return $lcm;
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

/**
 * @param string $file
 * @return Module[]
 */
function extractModules(string $file): array
{
    preg_match_all('/^(?<type>[%&])?(?<name>[a-z]+) -> (?<outputs>[a-z, ]+)$/m', $file, $matches, PREG_SET_ORDER);

    $moduleMap = array_reduce($matches, function (array $map, array $matches): array {
        ['type' => $type, 'name' => $name, 'outputs' => $outputs] = $matches;

        $instance = new Module(ModuleType::from($type), $name);
        // Inputs are derived later by reverse mapping all outputs
        $inputs = [];
        $outputs = explode(', ', $outputs);

        return $map + [$name => compact('instance', 'inputs', 'outputs')];
    }, [BUTTON => ['instance' => new Module(ModuleType::NONE, BUTTON), 'inputs' => [], 'outputs' => [BROADCASTER]]]);

    foreach ($moduleMap as $name => $moduleArray) {
        foreach ($moduleArray['outputs'] as $output) {
            if (!isset($moduleMap[$output])) {
                $moduleMap[$output] = ['instance' => new Module(ModuleType::NONE, $output), 'inputs' => [], 'outputs' => []];
            }
            $moduleMap[$output]['inputs'][] = $name;
        }
    }

    return array_map(function (array $moduleArray) use ($moduleMap): Module {
        /** @var Module $module */
        $module = $moduleArray['instance'];
        $moduleArray = $moduleMap[$module->name()];

        $module->inputs = array_map(fn (string $input): Module => $moduleMap[$input]['instance'], $moduleArray['inputs']);
        $module->outputs = array_map(fn (string $output): Module => $moduleMap[$output]['instance'], $moduleArray['outputs']);

        return $module;
    }, $moduleMap);
}

/**
 * Prints an indented list of the conjunction modules leading up to `rx`.
 *
 * This is written knowning that input data is similar to:
 * https://www.reddit.com/r/adventofcode/comments/18mypla/2023_day_20_input_data_plot/
 *
 * @param Module[] $modules
 * @param string $name
 * @param int $depth
 * @return string[] Names of the top-level conjunction modules
 */
function findTopConjunctionsLeadingTo(string $name, array $modules, int $depth = 0): array
{
    debug(fn () => sprintf('%s%s', str_repeat('  ', $depth), $name));

    $topConjunctions = [];

    $noConjunctions = true;
    foreach ($modules[$name]->inputs as $input) {
        if ($input->type() === ModuleType::CONJUNCTION) {
            $topConjunctions = array_merge($topConjunctions, findTopConjunctionsLeadingTo($input->name(), $modules, $depth + 1));
            $noConjunctions = false;
        }
    }

    return ($noConjunctions) ? [$name] : $topConjunctions;
}

function gcd(int $a, int $b): int
{
    return ($a % $b) ? gcd($b, $a % $b) : $b;
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
    if (($_SERVER['DEBUG'] ?? false) && (!is_numeric($_SERVER['DEBUG']) || (int)$_SERVER['DEBUG'] >= $level)) {
        echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
    }
}
