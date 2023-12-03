<?php
// https://adventofcode.com/2023/day/3

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    $numberByCoordinates = [];
    $symbolByCoordinates = [];
    foreach ($lines as $row => $line) {
        $number = '';
        foreach (str_split($line) as $col => $char) {
            if (is_numeric($char)) {
                $number .= $char;
                continue;
            }

            if ($char !== '.') {
                $symbolByCoordinates[$row][$col] = $char;
            }

            if ($number !== '') {
                $numberByCoordinates[$row][$col - strlen($number)] = $number;
            }
            $number = '';
        }
        if ($number !== '') {
            $numberByCoordinates[$row][$col - strlen($number)] = $number;
        }
        unset($number);
    }

    $symbolRows = array_keys($symbolByCoordinates);
    $symbolRowBounds = ['min' => min($symbolRows), 'max' => max($symbolRows)];
    $findParts = function(int $row, int $col, string $number) use ($symbolByCoordinates, $symbolRowBounds) : array {
        $parts = [];

        $prevRow = max($row - 1, $symbolRowBounds['min']);
        $nextRow = min($row + 1, $symbolRowBounds['max']);
        for ($adjacentRow = $prevRow; $adjacentRow <= $nextRow; $adjacentRow++) {
            $symbolByCol = $symbolByCoordinates[$adjacentRow] ?? [];
            if (!$symbolByCol) {
                continue;
            }

            $symbolCols = array_keys($symbolByCol);
            $symbolColBounds = ['min' => min($symbolCols), 'max' => max($symbolCols)];

            $left = max($col - 1, $symbolColBounds['min']);
            $right = min($col + strlen($number), $symbolColBounds['max']);
            for ($adjacentCol = $left; $adjacentCol <= $right; $adjacentCol++) {
                $symbol = $symbolByCol[$adjacentCol] ?? null;
                if ($symbol) {
                    $parts[] = [
                        'row' => $adjacentRow,
                        'col' => $adjacentCol,
                        'symbol' => $symbol,
                    ];
                }
            }
        }

        return $parts;
    };

    $partByCoordinate = [];
    foreach ($numberByCoordinates as $row => $numberByCol) {
        foreach ($numberByCol as $col => $number) {
            $parts = $findParts($row, $col, $number);
            if ($parts) {
                foreach ($parts as $part) {
                    $coordinates = $part['row'] . ', ' . $part['col'];
                    $partByCoordinate[$coordinates]['symbol'] = $part['symbol'];
                    $partByCoordinate[$coordinates]['numbers'][] = $number;
                }
            }
        }
    }

    $partNumbers = [];
    foreach ($partByCoordinate as $part) {
        $partNumbers = array_merge($partNumbers, $part['numbers']);
    }


    $gearRatios = [];
    foreach ($partByCoordinate as $part) {
        if ($part['symbol'] === '*' && count($part['numbers']) === 2) {
            $gearRatios[] = array_reduce($part['numbers'], fn($product, $number) => $product * $number, 1);
        }
    }

    printf("Sum of part numbers: %d\n", array_sum($partNumbers));
    printf("Sum of gear ratios: %d\n", array_sum($gearRatios));
})($argv[1] ?? 'input.txt');
