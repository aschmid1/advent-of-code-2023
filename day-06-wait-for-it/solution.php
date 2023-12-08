<?php
// https://adventofcode.com/2023/day/6

(function (string $fileName) {
    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    list($times, $distances) = array_map(function (string $line): array {
        list(, $numberString) = explode(': ', $line, 2);
        $numbers = [];
        for ($token = strtok($numberString, ' '); $token !== false; $token = strtok(' ')) {
            $numbers[] = (int)$token;
        }
        return $numbers;
    }, $lines);

    /**
     * For the example given in the question, the results can be charted like so:
     *  x=0, d=0  = 0(7-0)
     *  x=1, d=6  = 1(7-1)
     *  x=2, d=10 = 2(7-2)
     *  x=3, d=12 = 3(7-3)
     *  x=4, d=12 = 4(7-4)
     *  x=5, d=10 = 5(7-5)
     *  x=6, d=6  = 6(7-6)
     *  x=7, d=0  = 7(7-7)
     * Where x is how long the button was held down for, and d is the distance travelled.
     * This leads to the more general formula
     *  d = x(t-x)
     * Which can be converted to the qqadratic equation
     *  x² - tx + d = 0
     * Then solved for x using the quadratic formula
     *  x = ( t ± √(t²-4d) ) / 2
     *
     * For the 3rd example, the answers are exact integers which means they match but do not beat the record.
     * This is easily solved with (floor + 1) and (ceil - 1) adjustments.
     */
    function solveForNumberOfButtonPressTimes(int $time, int $distance): int {
        $timeOver2 = (float)$time / 2;
        $sqrtOver2 = sqrt($time ** 2 - 4 * $distance) / 2;
        $lowerBound = (int)floor($timeOver2 - $sqrtOver2) + 1;
        $upperBound = (int)ceil($timeOver2 + $sqrtOver2) - 1;

        return ($upperBound + 1) - $lowerBound;
    }

    $t0 = microtime(true);

    $numberOfPressTimesByRace = array_map('solveForNumberOfButtonPressTimes', $times, $distances);
    $productOfNumberOfPressTimesByRace = array_product($numberOfPressTimesByRace);

    $t1 = microtime(true);

    list($singleTime, $singleDistance) = array_map(fn($numbers) => (int)implode('', $numbers), [$times, $distances]);
    $numberOfPressTimes = solveForNumberOfButtonPressTimes($singleTime, $singleDistance);

    $t2 = microtime(true);

    printf("Product of number of ways to beat the records: %d in %.6f sec\n", $productOfNumberOfPressTimesByRace, $t1 - $t0);
    printf("Number of ways to beat the record of the bit race: %d in %.6f sec\n", $numberOfPressTimes, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
