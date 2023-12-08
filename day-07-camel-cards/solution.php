<?php

/**
 * @see https://adventofcode.com/2023/day/7
 */
(function (string $fileName) {
    function debug(callable $getMessage): void
    {
        if ($_SERVER['DEBUG'] ?? false) {
            echo rtrim($getMessage(), PHP_EOL) . PHP_EOL;
        }
    }

    $file = file_get_contents($fileName);
    $lines = array_filter(array_map('trim', explode(PHP_EOL, $file)));

    class Hand
    {
        public string $cards;
        public int $bid;
        public array $matches;
        public ?HandType $handType = null;
        public function __construct(string $cards, int $bid)
        {
            $this->cards = $cards;
            $this->bid = $bid;
            $this->matches = array_count_values(str_split($cards));
            arsort($this->matches);
        }
    }

    class HandType
    {
        public const FIVE_OF_A_KIND = 'Five of a kind';
        public const FOUR_OF_A_KIND = 'Four of a kind';
        public const FULL_HOUSE = 'Full house';
        public const THREE_OF_A_KIND = 'Three of a kind';
        public const TWO_PAIR = 'Two pair';
        public const ONE_PAIR = 'One pair';
        public const HIGH_CARD = 'High card';

        public string $type;
        public int $strength;
        public int $primaryMatches;
        public int $secondaryMatches;

        /**
         * @return HandType[]
         */
        public static function all(): array
        {
            return [
                new HandType(self::FIVE_OF_A_KIND, 7, 5, 0),
                new HandType(self::FOUR_OF_A_KIND, 6, 4, 1),
                new HandType(self::FULL_HOUSE, 5, 3, 2),
                new HandType(self::THREE_OF_A_KIND, 4, 3, 1),
                new HandType(self::TWO_PAIR, 3, 2, 2),
                new HandType(self::ONE_PAIR, 2, 2, 1),
                new HandType(self::HIGH_CARD, 1, 1, 1),
            ];
        }

        public function __construct(string $type, int $strength, int $primaryMatches, int $secondaryMatches)
        {
            $this->type = $type;
            $this->strength = $strength;
            $this->primaryMatches = $primaryMatches;
            $this->secondaryMatches = $secondaryMatches;
        }
    }

    $handTypesByMatches = array_reduce(HandType::all(), function (array $map, HandType $type): array {
        $map[$type->primaryMatches][$type->secondaryMatches] = $type;

        return $map;
    }, []);
    $getHandType = function (array $handMatches) use ($handTypesByMatches): HandType {
        $handMatches = array_values($handMatches);
        $primaryMatches = $handMatches[0];
        $secondaryMatches = $handMatches[1] ?? 0;

        return $handTypesByMatches[$primaryMatches][$secondaryMatches];
    };

    function sortHandsByRank(array $hands, bool $withWildcard): array
    {
        $strengthByFaceCard = [
            'A' => 14,
            'K' => 13,
            'Q' => 12,
            'J' => $withWildcard ? 1 : 11,
            'T' => 10,
        ];
        usort($hands, function (Hand $handA, Hand $handB) use ($strengthByFaceCard): int {
            if ($handA->handType->strength !== $handB->handType->strength) {
                return $handA->handType->strength <=> $handB->handType->strength;
            }

            for ($i = 0; $i < 5; $i++) {
                $cardA = $handA->cards[$i];
                $cardB = $handB->cards[$i];
                if ($cardA !== $cardB) {
                    $strengthA = (int)($strengthByFaceCard[$cardA] ?? $cardA);
                    $strengthB = (int)($strengthByFaceCard[$cardB] ?? $cardB);

                    return $strengthA <=> $strengthB;
                }
            }

            return 0;
        });
        return $hands;
    }

    // Part 1
    $t0 = microtime(true);

    $hands = array_map(fn ($line) => new Hand(...explode(' ', $line, 2)), $lines);
    $hands = array_map(function (Hand $hand) use ($getHandType) {
        $hand->handType = $getHandType($hand->matches);

        return $hand;
    }, $hands);
    $hands = sortHandsByRank($hands, false);
    $winnings = array_sum(array_map(fn (Hand $hand, int $rank) => $hand->bid * $rank, $hands, range(1, count($hands))));

    // Part 2
    $t1 = microtime(true);

    $wildHands = array_map(fn ($line) => new Hand(...explode(' ', $line, 2)), $lines);
    $wildHands = array_map(function (Hand $hand) use ($getHandType) {
        $handMatches = $hand->matches;

        $jokerMatches = (int)($handMatches['J'] ?? 0);
        if ($jokerMatches) {
            unset($handMatches['J']);
            $primaryCard = (string)key($handMatches);
            $handMatches[$primaryCard] += $jokerMatches;
        }

        $hand->handType = $getHandType($handMatches);

        return $hand;
    }, $wildHands);
    $wildHands = sortHandsByRank($wildHands, true);
    $wildWinnings = array_sum(array_map(fn (Hand $hand, int $rank) => $hand->bid * $rank, $wildHands, range(1, count($wildHands))));

    $t2 = microtime(true);

    printf("Part 1 Total winnings: %d in %.6f sec\n", $winnings, $t1 - $t0);
    printf("Part 2 Wildcard winnings: %d in %.6f sec\n", $wildWinnings, $t2 - $t1);
    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');
