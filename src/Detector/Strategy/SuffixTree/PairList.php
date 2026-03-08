<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

use Systemsdk\PhpCPD\Exceptions\OutOfBoundsException;

use function array_slice;

/**
 * A list for storing pairs in a specific order.
 *
 * @template T
 * @template S
 */
class PairList
{
    /**
     * The current size.
     */
    private int $size = 0;

    /**
     * The array used for storing the S.
     *
     * @var array<int, mixed>
     */
    private array $firstElements;

    /**
     * The array used for storing the T.
     *
     * @var array<int, mixed>
     */
    private array $secondElements;

    public function __construct(int $initialCapacity) // , $firstType, $secondType
    {
        if ($initialCapacity < 1) {
            $initialCapacity = 1;
        }

        $data = array_fill(0, $initialCapacity, null);
        $this->firstElements = $data;
        $this->secondElements = $data;
    }

    /**
     * Returns the size of the list.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Add the given pair to the list.
     *
     * @param S $first
     * @param T $second
     */
    public function add($first, $second): void
    {
        $this->firstElements[$this->size] = $first;
        $this->secondElements[$this->size] = $second;
        $this->size++;
    }

    /**
     * Returns the first element at given index.
     *
     * @return S
     */
    public function getFirst(int $i)
    {
        $this->checkWithinBounds($i);

        return $this->firstElements[$i];
    }

    /**
     * Returns the second element at given index.
     *
     * @return T
     */
    public function getSecond(int $i)
    {
        $this->checkWithinBounds($i);

        return $this->secondElements[$i];
    }

    /**
     * Creates a new list containing all first elements.
     *
     * @return S[]
     */
    public function extractFirstList(): array
    {
        return array_slice($this->firstElements, 0, $this->size);
    }

    /**
     * Checks whether the given <code>$i</code> is within the bounds. Throws an
     * exception otherwise.
     */
    private function checkWithinBounds(int $i): void
    {
        if ($i < 0 || $i >= $this->size) {
            throw new OutOfBoundsException('Out of bounds: ' . $i);
        }
    }
}
