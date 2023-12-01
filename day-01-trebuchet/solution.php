<?php
// https://adventofcode.com/2023/day/1

(function (string $fileName) {
    $digitsMap = [
        'one' => '1',
        'two' => '2',
        'three' => '3',
        'four' => '4',
        'five' => '5',
        'six' => '6',
        'seven' => '7',
        'eight' => '8',
        'nine' => '9',
    ];
    $digitMinLength = 3;
    $digitMaxLength = 5;

    $getDigit = function (array $chars, bool $reverse) use ($digitsMap, $digitMinLength): string {
        $buildSubstring = fn($substring, $char) => $substring . $char;
        $substringHasText = 'str_ends_with';
        if ($reverse) {
            $buildSubstring = fn($substring, $char) => $char . $substring;
            $substringHasText = 'str_starts_with';
            $chars = array_reverse($chars);
        }

        $substring = '';
        foreach ($chars as $char) {
            if (is_numeric($char)) {
                return $char;
            }

            $substring = $buildSubstring($substring, $char);
            if (strlen($substring) >= $digitMinLength) {
                foreach ($digitsMap as $text => $number) {
                    if ($substringHasText($substring, $text)) {
                        return $number;
                    }
                }
            }
        }

        return '';
    };

    $calibrations = [];

    $file = fopen($fileName, 'r');
    while (!feof($file)) {
        $original = trim(fgets($file));

        $length = strlen($original);
        if ($length <= 0) {
            continue;
        }

        $chars = array_map(fn($i) => $original[$i], range(0, $length - 1));
        $firstDigit = $getDigit(chars: $chars, reverse: false);
        $lastDigit = $getDigit(chars: $chars, reverse: true);

        $calibrations[] = [
            'original' => $original,
            'value' => (int)($firstDigit . $lastDigit),
        ];
    }
    fclose($file);

    echo print_r($calibrations, true) . PHP_EOL;
    echo 'sum: ' . array_sum(array_column($calibrations, 'value')) . PHP_EOL;
})($argv[1] ?? 'input.txt');
