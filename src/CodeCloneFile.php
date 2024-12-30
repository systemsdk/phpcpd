<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD;

final readonly class CodeCloneFile
{
    private string $name;

    private int $startLine;

    private string $id;

    public function __construct(string $name, int $startLine)
    {
        $this->name = $name;
        $this->startLine = $startLine;
        $this->id = $this->name . ':' . $this->startLine;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function startLine(): int
    {
        return $this->startLine;
    }
}
