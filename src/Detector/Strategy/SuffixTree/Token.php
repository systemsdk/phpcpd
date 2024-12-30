<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

class Token extends AbstractToken
{
    public function __construct(
        int $tokenCode,
        string $tokenName,
        int $line,
        string $file,
        string $content
    ) {
        $this->tokenCode = $tokenCode;
        $this->tokenName = $tokenName;
        $this->line = $line;
        $this->content = $content;
        $this->file = $file;
    }

    public function __toString(): string
    {
        return $this->tokenName;
    }

    public function hashCode(): int
    {
        return crc32($this->content);
    }

    public function equals(AbstractToken $other): bool
    {
        return $other->hashCode() === $this->hashCode();
    }
}
