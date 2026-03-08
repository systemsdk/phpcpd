<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

abstract class AbstractToken
{
    public protected(set) int $tokenCode;
    public protected(set) int $line;
    public protected(set) string $file;
    public protected(set) string $tokenName;
    public protected(set) string $content;

    abstract public function __toString(): string;

    abstract public function equals(self $other): bool;
}
