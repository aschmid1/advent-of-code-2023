<?php
/**
 * @see https://adventofcode.com/2023/day/17
 *
 * The key to this one is how you model the nodes used for the djikstra/A* algorithm.
 *
 * My biggest sticking point was pruning nodes by checking next cost against
 * best cost AFTER dequeing them instead of BEFORE enqueuing them. It makes
 * sense in hindsight that the former enqueues excess high cost (low priority)
 * nodes that have to be sorted each iteration and may never be processed.
 */

namespace AdventOfCode2023\Day17;

const NORTH = '^';
const SOUTH = 'v';
const EAST = '>';
const WEST = '<';

/**
 * Value object representing a node in a graph.
 */
final class Node
{
    public static function create(Point $point, string $direction, int $steps): Node
    {
        static $cache = [];
        return ($cache[(string)$point][$direction][$steps] ??= new self($point, $direction, $steps));
    }

    private function __construct(
        public Point $point,
        public string $direction,
        public int $steps
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s %s %d', $this->point, $this->direction, $this->steps);
    }
}

/**
 * Value object representing a point on a grid.
 */
final class Point
{
    public static function create(int $x, int $y): Point
    {
        static $cache = [];
        return ($cache[$x][$y] ??= new self($x, $y));
    }

    public static function createRelative(Point $from, string $direction, int $distance = 1): Point
    {
        $x = $from->x;
        $y = $from->y;

        switch ($direction) {
            case NORTH:
                $y -= $distance;
                break;
            case SOUTH:
                $y += $distance;
                break;
            case EAST:
                $x += $distance;
                break;
            case WEST:
                $x -= $distance;
                break;
        }

        return static::create($x, $y);
    }

    private function __construct(
        public int $x,
        public int $y,
    ) {
    }

    public function equalsTo(Point $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }

    public function distanceTo(Point $other): int
    {
        return abs($this->x - $other->x) + abs($this->y - $other->y);
    }

    public function __toString(): string
    {
        return sprintf('(%d, %d)', $this->x, $this->y);
    }
}

/**
 * Linked list of nodes representing the path to the goal.
 *
 * Not required for this problem, but useful for troubleshooting.
 */
final class NodePath
{
    /** @var string[] */
    private array $nodeLinks;

    /** @var Node[] */
    private array $nodeMap;

    public function overlayGrid(Node $goal, array $grid): array
    {
        $path = $this->getPathArray($goal);
        foreach ($path as $goal) {
            $grid[$goal->point->x][$goal->point->y] = $goal->direction;
        }
        return $grid;
    }

    /**
     * @param Node $goal
     * @return Node[]
     */
    public function getPathArray(Node $goal): array
    {
        $path = [$goal];
        while ($this->hasParent($goal)) {
            $goal = $this->getParent($goal);
            $path[] = $goal;
        }
        return array_reverse($path);
    }

    public function linkNodes(Node $from, Node $to): void
    {
        $this->nodeMap[(string)$from] = $from;
        $this->nodeMap[(string)$to] = $to;

        $this->nodeLinks[(string)$to] = (string)$from;
    }

    public function hasParent(Node $node): bool
    {
        return isset($this->nodeLinks[(string)$node]);
    }

    public function getParent(Node $node): Node
    {
        if (!$this->hasParent($node)) {
            throw new \InvalidArgumentException('Node has no parent: ' . $node);
        }
        return $this->nodeMap[$this->nodeLinks[(string)$node]];
    }
}

function findShortestPath(array $grid, int $minSteps = 1, int $maxSteps = 3): int
{
    $queue = new \SplPriorityQueue();
    $queue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);

    $goal = Point::create(count($grid) - 1, count($grid[0]) - 1);
    $nodeCosts = [];
    $nodePath = new NodePath();

    $enqueueNode = function (Node $node, int $nextCost) use ($goal, $queue, &$nodeCosts): bool {
        // I got bit by using > instead of >= here. Big difference in excess nodes enqueued.
        if ($nextCost >= ($nodeCosts[(string)$node] ?? PHP_INT_MAX)) {
            return false;
        }
        $nodeCosts[(string)$node] = $nextCost;

        $priority = $nextCost + getHeuristic($node->point, $goal);
        // SplPriorityQueue extracts the highest value first, but we want the lowest instead
        $priority = -$priority;

        debug(fn () => sprintf("\t" . '%s: C=%d P=%d', $node, $nextCost, $priority), 3);

        return $queue->insert($node, $priority);
    };

    $startingNodes = [
        Node::create(Point::create(1, 0), EAST, 1),
        Node::create(Point::create(0, 1), SOUTH, 1),
    ];
    foreach ($startingNodes as $startingNode) {
        $enqueueNode($startingNode, $grid[$startingNode->point->x][$startingNode->point->y]);
    }

    while ($queue->valid()) {
        $extracted = $queue->extract();
        /** @var Node $node */
        ['data' => $node, 'priority' => $priority] = $extracted;
        $costSoFar = $nodeCosts[(string)$node];

        debug(fn () => sprintf('%s: C=%d P=%d', $node, $costSoFar, $priority), 3);

        if ($node->point->equalsTo($goal) && $node->steps >= $minSteps) {
            debug(fn () => implode(PHP_EOL, array_map('implode', transpose($nodePath->overlayGrid($node, $grid)))), 2);

            return $costSoFar;
        }

        foreach (getNextDirections($node->direction) as $nextDirection) {
            $isTurning = $nextDirection !== $node->direction;
            $nextSteps = (!$isTurning) ? ($node->steps + 1) : 1;

            if (
                ($isTurning && $node->steps < $minSteps)
                || (!$isTurning && $nextSteps > $maxSteps)
            ) {
                continue;
            }

            $nextPoint = Point::createRelative($node->point, $nextDirection, 1);
            if (!isset($grid[$nextPoint->x][$nextPoint->y])) {
                continue;
            }
            if ($isTurning) {
                $nextStoppingPoint = Point::createRelative($node->point, $nextDirection, $minSteps);
                if (!isset($grid[$nextStoppingPoint->x][$nextStoppingPoint->y])) {
                    continue;
                }
            }

            $nextCost = $costSoFar + $grid[$nextPoint->x][$nextPoint->y];
            $nextNode = Node::create($nextPoint, $nextDirection, $nextSteps);

            if ($enqueueNode($nextNode, $nextCost) && ($_SERVER['DEBUG'] ?? 0) === 2) {
                $nodePath->linkNodes($node, $nextNode);
            }
        }
    }

    throw new \RuntimeException('No path found');
}

/**
 * Valid directions are forward or a 90 degree turn.
 *
 * @param string $direction
 * @return string[]
 */
function getNextDirections(string $direction): array
{
    switch ($direction) {
        case NORTH:
        case SOUTH:
            return [$direction, EAST, WEST];
        case EAST:
        case WEST:
            return [$direction, NORTH, SOUTH];
    }

    throw new \InvalidArgumentException('Invalid direction');
}

function getHeuristic(Point $point, Point $goal): int
{
    return $point->distanceTo($goal);
}

(function (string $fileName) {
    $file = file_get_contents($fileName);
    // Make this an x, y grid for consist referencing as $grid[$x][$y]
    $grid = transpose(array_map('str_split', explode(PHP_EOL, trim($file))));
    debug(fn () => print_r(array_map('implode', $grid), true), 4);

    runPart(1, function () use ($grid): int {
        return findShortestPath($grid);
    });

    debug(fn () => str_repeat('-', 80));

    runPart(2, function () use ($grid): int {
        return findShortestPath($grid, 4, 10);
    });

    printf("memory_get_peak_usage(): %s KB\n", memory_get_peak_usage() / 1000);
})($argv[1] ?? 'input.txt');

function transpose(array $grid): array
{
    return array_map(null, ...$grid);
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
