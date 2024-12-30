<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

use function random_int;

/**
 * A sentinel character which can be used to produce explicit leaves for all
 * suffixes. The sentinel just has to be appended to the list before handing
 * it to the suffix tree. For the sentinel equality and object identity are
 * the same!
 */
class Sentinel extends AbstractToken
{
    /**
     * @var int The hash value used.
     */
    private $hash;

    public function __construct()
    {
        $this->hash = random_int(0, PHP_INT_MAX);
        $this->tokenCode = -1;
        $this->line = -1;
        $this->file = '<no file>';
        $this->tokenName = '<no token name>';
        $this->content = '<no token content>';
    }

    public function __toString(): string
    {
        return '$';
    }

    public function hashCode(): int
    {
        return $this->hash;
    }

    public function equals(AbstractToken $other): bool
    {
        // Original code uses physical object equality, not present in PHP.
        return $other instanceof self;
    }
}
