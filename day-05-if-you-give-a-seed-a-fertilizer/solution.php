<?php
// https://adventofcode.com/2023/day/5

(function (string $fileName) {
    $file = file_get_contents($fileName);

    preg_match_all('/(?<labels>[a-z-]+)(?: map)?:\s+(?<valueFile>[0-9\s]+)/', $file, $matches);
    $sections = array_map(function (int $i) use ($matches): array {
        $label = $matches['labels'][$i];
        $valueFile = $matches['valueFile'][$i];

        $valueLines = array_filter(array_map('trim', explode(PHP_EOL, $valueFile)));
        $valueRows = array_map(fn(string $line) => array_map('intval', explode(' ', $line)), $valueLines);

        return compact('label', 'valueRows');
    }, array_keys($matches['labels']));
    $seedSection = array_shift($sections);

    $seeds = (array)$seedSection['valueRows'][0];
    $seedRanges = array_map(fn($i) => ['start' => $seeds[$i * 2], 'length' => $seeds[($i * 2) + 1]], range(0, (count($seeds) / 2) - 1));

    $maps = array_map(function (array $section): array {
        $label = (string)$section['label'];
        $valueRows = (array)$section['valueRows'];

        list($source, $destination) = explode('-to-', $label, 2);

        $ranges = array_map(function ($valueRow) {
            list($destination, $source, $length) = $valueRow;

            return compact('source', 'destination', 'length');
        }, $valueRows);

        return compact('source', 'destination', 'ranges');
    }, $sections);
    $maps = array_combine(array_column($maps, 'source'), $maps);

    $traversePoints = function () use ($seeds, $maps): array {
        return array_map(function (int $seed) use ($maps): array {
            $sourceName = 'seed';
            $sourceValue = $seed;

            $valueByCategory = [$sourceName => $sourceValue];

            while (($map = (array)($maps[$sourceName] ?? []))) {
                $destinationName = (string)$map['destination'];
                $destinationValue = $sourceValue;

                foreach ($map['ranges'] as $mapRange) {
                    $mapLength = (int)$mapRange['length'];
                    $mapSourceStart = (int)$mapRange['source'];
                    $mapDestinationStart = (int)$mapRange['destination'];

                    $offset = $sourceValue - $mapSourceStart;
                    if ($offset >= 0 && $offset < $mapLength) {
                        $destinationValue = $mapDestinationStart + $offset;
                        break;
                    }
                }

                $valueByCategory[$destinationName] = $destinationValue;

                $sourceName = $destinationName;
                $sourceValue = $destinationValue;
            }

            return $valueByCategory;
        }, $seeds);
    };

    $traverseRanges = function () use ($seedRanges, $maps): array {
        // Uncomment to stub this function
        //return ['location' => [0 => ['start' => PHP_INT_MAX, 'length' => 1]]];

        $sourceName = 'seed';
        $sourceRanges = $seedRanges;

        $rangesByCategory[$sourceName] = $sourceRanges;

        while (($map = (array)($maps[$sourceName] ?? []))) {
            $destinationName = (string)$map['destination'];

            $remainingSourceRanges = $sourceRanges;
            $mappedRanges = [];
            foreach ($map['ranges'] as $mapRange) {
                $mapLength = (int)$mapRange['length'];
                $mapSourceStart = (int)$mapRange['source'];
                $mapDestinationStart = (int)$mapRange['destination'];
                $mapSourceEnd = $mapSourceStart + $mapLength;

                $splitSourceRanges = [];
                foreach ($remainingSourceRanges as $sourceRange) {
                    $sourceStart = (int)$sourceRange['start'];
                    $sourceLength = (int)$sourceRange['length'];
                    $sourceEnd = $sourceStart + $sourceLength;

                    $mappedRangeStart = max($sourceStart, $mapSourceStart);
                    $mappedRangeEnd = min($sourceEnd, $mapSourceEnd);
                    $mappedLength = $mappedRangeEnd - $mappedRangeStart;
                    $mappedOffset = $mappedRangeStart - $mapSourceStart;

                    $mappedRange = ['start' => $mapDestinationStart + $mappedOffset, 'length' => $mappedLength];

                    if ($mappedRange['length'] <= 0) {
                        $splitSourceRanges[] = $sourceRange;
                        continue;
                    }

                    $mappedRanges[] = $mappedRange;

                    $beforeRange = ['start' => $sourceStart, 'length' => $mapSourceStart - $sourceStart];
                    if ($beforeRange['length'] > 0) {
                        $splitSourceRanges[] = $beforeRange;
                    }

                    $afterRange = ['start' => $mapSourceEnd, 'length' => $sourceEnd - $mapSourceEnd];
                    if ($afterRange['length'] > 0) {
                        $splitSourceRanges[] = $afterRange;
                    }
                }

                $remainingSourceRanges = $splitSourceRanges;
                if (!$remainingSourceRanges) {
                    break;
                }
            }

            $destinationRanges = array_merge($mappedRanges, $remainingSourceRanges);

            $rangesByCategory[$destinationName] = $destinationRanges;

            $sourceName = $destinationName;
            $sourceRanges = $destinationRanges;
        }

        return $rangesByCategory;
    };

    $t0 = microtime(true);

    $seedPointMappings = $traversePoints();
    $locationPoints = array_column($seedPointMappings, 'location');
    $lowestLocationFromPoints = (int)min($locationPoints);

    $t1 = microtime(true);

    $seedRangeMappings = $traverseRanges();
    $locationStartingPoints = array_column($seedRangeMappings['location'], 'start');
    $lowestLocationFromRanges = (int)min($locationStartingPoints);

    $t2 = microtime(true);

    printf("Lowest location from points: %d in %.6f sec\n", $lowestLocationFromPoints, $t1 - $t0);
    printf("Lowest location from ranges: %d in %.6f sec\n", $lowestLocationFromRanges, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
