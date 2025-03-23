<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

abstract class AbstractToken
{
    public int $tokenCode;
    public int $line;
    public string $file;
    public string $tokenName;
    public string $content;

    abstract public function __toString(): string;

    abstract public function hashCode(): int;

    abstract public function equals(self $other): bool;
}
