<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD;

final readonly class CodeCloneFile
{
    private string $id;
    private string $name;
    private int $startLine;
    private ?int $endLine;

    public function __construct(string $name, int $startLine, ?int $endLine = null)
    {
        $this->id = $name . ':' . $startLine;
        $this->name = $name;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
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

    public function endLine(): ?int
    {
        return $this->endLine;
    }
}
