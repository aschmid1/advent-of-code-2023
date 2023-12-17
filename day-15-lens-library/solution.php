<?php

namespace AdventOfCode2023\Day15;

/**
 * @see https://adventofcode.com/2023/day/15
 */
(function (string $fileName) {
    $file = file_get_contents($fileName);
    $steps = explode(',', trim($file));

    runPart(1, function () use ($steps): int {
        return array_sum(array_map(fn (string $step): int => hashString($step), $steps));
    });

    runPart(2, function () use ($steps): int {
        return calculateFocusingPower(hashMapBoxes($steps));
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

function calculateFocusingPower(array $boxes): int
{
    $focusingPower = 0;

    foreach ($boxes as $box => $lenses) {
        $boxNumber = $box + 1;
        $lenseSlot = 1;
        foreach ($lenses as $label => $focalLength) {
            $lensePower = $boxNumber * $lenseSlot * $focalLength;
            debug(fn () => sprintf('- %s: %d (box %d) * %d (first slot) * %d (focal length) = %d', $label, $boxNumber, $box, $lenseSlot, $focalLength, $lensePower), 2);
            $lenseSlot++;

            $focusingPower += $lensePower;

        }
    }

    return $focusingPower;
}

function hashMapBoxes(array $steps): array
{
    $boxes = [];
    foreach ($steps as $step) {
        $operation = (strpos($step, '=') !== false) ? '=' : '-';
        [$label, $focalLength] = explode($operation, $step, 2);
        $box = hashString($label);

        if ($focalLength) {
            $boxes[$box][$label] = (int)$focalLength;
        } else {
            unset($boxes[$box][$label]);
            if (empty($boxes[$box])) {
                unset($boxes[$box]);
            }
        }

        debug(fn () => sprintf('After "%s":', $step, 2));
        foreach ($boxes as $box => $lenses) {
            debug(fn () => sprintf('    Box %d: [%s]', $box, implode('] [', $lenses)), 2);
        }
    }

    return $boxes;
}

function hashString(string $string): int
{
    $value = 0;
    for ($i = 0; $i < strlen($string); $i++) {
        $value = hashChar($string[$i], $value);
    }

    return $value;
}

function hashChar(string $char, int $value): int
{
    $value += ord($char);
    $value *= 17;
    $value %= 256;

    return $value;
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
