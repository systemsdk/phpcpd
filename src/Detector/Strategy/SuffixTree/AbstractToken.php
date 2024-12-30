<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

abstract class AbstractToken
{
    /**
     * @var int
     */
    public $tokenCode;

    /**
     * @var int
     */
    public $line;

    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    public $tokenName;

    /**
     * @var string
     */
    public $content;

    abstract public function __toString(): string;

    abstract public function hashCode(): int;

    abstract public function equals(self $other): bool;
}
