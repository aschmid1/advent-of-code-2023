<?php

/**
 * @see https://adventofcode.com/2023/day/8
 */
(function (string $fileName) {
    function debug(callable $getMessage): void
    {
        if ($_SERVER['DEBUG'] ?? false) {
            echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
        }
    }
    $file = file_get_contents($fileName);

    list($directionsString, $nodesFile) = explode(PHP_EOL, $file, 2);
    preg_match_all('/(?<node>\w+) = \((?<L>\w+), (?<R>\w+)\)/', $nodesFile, $matches);

    $directions = str_split($directionsString);
    $numberOfDirections = count($directions);
    $network = array_map(fn ($node, $L, $R) => compact('node', 'L', 'R'), $matches['node'], $matches['L'], $matches['R']);
    $network = array_combine(array_column($network, 'node'), $network);

    $part1Traversal = function () use ($directions, $numberOfDirections, $network): int {
        $history = [];
        $node = 'AAA';
        $step = 0;
        while ($node !== 'ZZZ') {
            $index = $step % $numberOfDirections;
            $direction = $directions[$index];

            if (isset($history[$index][$node])) {
                printf('Part 1: Found loop at step %d for node %s with length %d' . PHP_EOL, $step, $node, $step - $history[$index][$node]);
                break;
            }
            $history[$index][$node] = $step;

            $node = $network[$node][$direction];
            $step += 1;
        }

        return $step;
    };

    $part2Traversal = function () use ($directions, $numberOfDirections, $network): int {
        $network = array_map(fn ($point) => array_merge($point, [
            'starting' => $point['node'][2] === 'A',
            'ending' => $point['node'][2] === 'Z',
        ]), $network);

        $startingNodes = array_keys(array_filter($network, fn ($point) => $point['starting']));
        $loopLengthsByStartingNode = array_fill_keys($startingNodes, null);

        $history = [];
        $currentNodes = $startingNodes;
        $step = 0;
        while (array_filter($currentNodes, fn ($node) => !$network[$node]['ending'])) {
            $index = $step % $numberOfDirections;
            $direction = $directions[$index];

            foreach ($currentNodes as $i => $node) {
                if (!isset($history[$index][$node])) {
                    $history[$index][$node] = $step;
                } else {
                    $loopLengthsByStartingNode[$startingNodes[$i]] ??= $step - $history[$index][$node];
                }
            }

            $currentNodes = array_map(fn ($node) => $network[$node][$direction], $currentNodes);
            $step += 1;

            if (!array_filter($loopLengthsByStartingNode, 'is_null')) {
                // Myself and Reddit dislike this solution because it assumes
                // that each path only ever traverses a single ending node.
                // But it has been correct for all inputs so far.
                function gcd(int $a, int $b): int
                {
                    return ($a % $b) ? gcd($b, $a % $b) : $b;
                }
                $lcm = array_reduce($loopLengthsByStartingNode, fn ($lcm, $loopLength) => $lcm * $loopLength / gcd($lcm, $loopLength), 1);

                return $lcm;
            }
        }

        return $step;
    };

    // Part 1
    $t0 = microtime(true);

    if ($fileName !== 'example3.txt') {
        $part1Steps = $part1Traversal();
    } else {
        print 'Example 3 does not work with part 1' . PHP_EOL;
        $part1Steps = null;
    }

    // Part 2
    $t1 = microtime(true);

    $part2Steps = $part2Traversal();

    $t2 = microtime(true);

    printf("Part 1 steps: %d in %.6f sec\n", $part1Steps, $t1 - $t0);
    printf("Part 2 steps: %d in %.6f sec\n", $part2Steps, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
