<?php
// https://adventofcode.com/2023/day/2

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    $offset = strlen('Game ');
    $parsedGames = array_map(function ($line) use ($offset) {
        list($gameString, $cubeSetsString) = explode(': ', $line, 2);

        $id = (int)substr($gameString, $offset);
        $cubeSets = array_map(fn(string $set) => array_reduce(explode(', ', $set), function(array $map, string $cube) {
            list($number, $colour) = explode(' ', $cube, 2);

            return $map + [$colour => (int)$number];
        }, []), explode('; ', $cubeSetsString));

        return compact('id', 'cubeSets');
    }, $lines);

    $allGames = array_map(function(array $game) {
        /** @var array<int, array<string, int>> $cubeSets */
        $cubeSets = (array)$game['cubeSets'];

        $bag = [];
        foreach ($cubeSets as $cubeSet) {
            foreach ($cubeSet as $colour => $number) {
                $bag[$colour] = (int)max($bag[$colour] ?? 0, $number);
            }
        }

        $power = array_reduce($bag, fn($power, $n) => $power * $n, 1);

        return $game + compact('bag', 'power');
    }, $parsedGames);

    $bagSet = ['red' => 12, 'green' => 13, 'blue' => 14];
    $validGames = array_filter($allGames, function(array $game) use ($bagSet) {
        /** @var array<int, array<string, int>> $cubeSets */
        $cubeSets = (array)$game['cubeSets'];

        foreach ($cubeSets as $cubeSet) {
            if (array_diff_key($cubeSet, $bagSet)) {
                return false;
            }
            foreach ($cubeSet as $colour => $number) {
                $bagNumber = (int)$bagSet[$colour];
                if ($number > $bagNumber) {
                    return false;
                }
            }
        }

        return true;
    });

    printf("Sum of valid ids: %d\n", array_sum(array_column($validGames, 'id')));
    printf("Sum of all powers: %d\n", array_sum(array_column($allGames, 'power')));
})($argv[1] ?? 'input.txt');
