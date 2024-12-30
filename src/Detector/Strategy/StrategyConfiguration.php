<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy;

use Systemsdk\PhpCPD\Cli\Arguments;

final readonly class StrategyConfiguration
{
    private int $minLines;

    private int $minTokens;

    private int $editDistance;

    private int $headEquality;

    private bool $fuzzy;

    public function __construct(Arguments $arguments)
    {
        $this->minLines = $arguments->linesThreshold();
        $this->minTokens = $arguments->tokensThreshold();
        $this->editDistance = $arguments->editDistance();
        $this->headEquality = $arguments->headEquality();
        $this->fuzzy = $arguments->fuzzy();
    }

    public function minLines(): int
    {
        return $this->minLines;
    }

    public function minTokens(): int
    {
        return $this->minTokens;
    }

    public function editDistance(): int
    {
        return $this->editDistance;
    }

    public function headEquality(): int
    {
        return $this->headEquality;
    }

    public function fuzzy(): bool
    {
        return $this->fuzzy;
    }
}
