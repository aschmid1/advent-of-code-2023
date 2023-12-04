<?php
// https://adventofcode.com/2023/day/4

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    $cards = array_map(function (string $line): array {
        list($cardStr, $allNumbersStr) = explode(': ', $line, 2);

        $id = (int)substr($cardStr, strlen('Card '));
        list($winningNumbers, $myNumbers) = array_map(function(string $numbersStr): array {
            $numbers = [];
            for ($number = strtok($numbersStr, ' '); $number !== false; $number = strtok(' ')) {
                $numbers[] = (int)$number;
            }

            return $numbers;
        }, explode(' | ', $allNumbersStr, 2));

        $numberOfMatches = count(array_intersect($winningNumbers, $myNumbers));

        return compact('id', 'winningNumbers', 'myNumbers', 'numberOfMatches');
    }, $lines);
    $cards = array_combine(array_column($cards, 'id'), $cards);

    $cardPoints = array_map(function (array $card): int {
        $numberOfMatches = (int)$card['numberOfMatches'];

        return ($numberOfMatches > 0) ? 2 ** ($numberOfMatches - 1) : 0;
    }, $cards);

    $cardTree = array_map(function (array $card): array {
        $id = (int)$card['id'];
        $numberOfMatches = (int)$card['numberOfMatches'];

        $copyIds = [];
        for ($nextId = $id + 1; $nextId <= $id + $numberOfMatches; $nextId++) {
            $copyIds[] = $nextId;
        }

        return $copyIds;
    }, $cards);

    function getCardCountsRecursive(array $cardTree, int $id = 0, array &$idCounts = []): array {
        if (!$id) {
            $childIds = array_keys($cardTree);
            $idCounts = array_fill_keys($childIds, 0);
        } else {
            $childIds = (array)$cardTree[$id];
            $idCounts[$id] += 1;
        }
        foreach ($childIds as $childId) {
            getCardCountsRecursive($cardTree, $childId, $idCounts);
        }

        return $idCounts;
    }

    // Leverage the fact that childId > parentId and that we are only after sums
    function getCardCountsSumAhead(array $cardTree): array {
        $idCounts = array_fill_keys(array_keys($cardTree), 1);

        foreach ($cardTree as $parentId => $childIds) {
            foreach ($childIds as $childId) {
                $idCounts[$childId] += $idCounts[$parentId];
            }
        }

        return $idCounts;
    }

    $t[0] = microtime(true);
    $cardCountsRecursive = getCardCountsRecursive($cardTree);
    $t[1] = microtime(true);
    $cardCountsSumAhead = getCardCountsSumAhead($cardTree);
    $t[2] = microtime(true);

    printf("Sum of points: %d\n", array_sum($cardPoints));
    printf("Total cards (recursive): %d in %.6f sec\n", array_sum($cardCountsRecursive), $t[1] - $t[0]);
    printf("Total cards (sum ahead): %d in %.6f sec\n", array_sum($cardCountsSumAhead), $t[2] - $t[1]);
})($argv[1] ?? 'input.txt');
