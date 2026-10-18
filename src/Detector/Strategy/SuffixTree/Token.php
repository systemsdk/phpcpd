<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

class Token extends AbstractToken
{
    /**
     * Maps unique token signatures to integer IDs for fast O(1) comparisons and array indexing.
     *
     * @var array<string, int>
     */
    private static array $hashRegistry = [];

    private static int $nextHashId = 1;

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

        $key = $tokenCode . '_' . $content;
        $this->hash = self::$hashRegistry[$key] ??= self::$nextHashId++;
    }

    public function __toString(): string
    {
        return $this->tokenName;
    }

    public function equals(AbstractToken $other): bool
    {
        if ($other === $this) {
            return true;
        }

        return $this->hash === $other->hash;
    }
}
