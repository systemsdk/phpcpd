<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD;

use Iterator;

use function array_reverse;
use function count;
use function usort;

final class CodeCloneMapIterator implements Iterator
{
    /**
     * @var array<int, CodeClone>
     */
    private array $clones;

    private int $position = 0;

    public function __construct(CodeCloneMap $clones)
    {
        $this->clones = $clones->clones();

        usort(
            $this->clones,
            static function (CodeClone $a, CodeClone $b): int {
                return $a->numberOfLines() <=> $b->numberOfLines();
            }
        );

        $this->clones = array_reverse($this->clones);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < count($this->clones);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): CodeClone
    {
        return $this->clones[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
