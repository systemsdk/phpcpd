<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Cli;

final readonly class Arguments
{
    /**
     * @param array<int, string> $directories
     * @param array<int, string> $suffixes
     * @param array<int, string> $exclude
     */
    public function __construct(
        private array $directories,
        private array $suffixes,
        private array $exclude,
        private ?string $pmdCpdXmlLogfile,
        private int $linesThreshold,
        private int $tokensThreshold,
        private bool $fuzzy,
        private bool $verbose,
        private bool $help,
        private bool $version,
        private string $algorithm,
        private int $editDistance,
        private int $headEquality
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function directories(): array
    {
        return $this->directories;
    }

    /**
     * @return array<int, string>
     */
    public function suffixes(): array
    {
        return $this->suffixes;
    }

    /**
     * @return array<int, string>
     */
    public function exclude(): array
    {
        return $this->exclude;
    }

    public function pmdCpdXmlLogfile(): ?string
    {
        return $this->pmdCpdXmlLogfile;
    }

    public function linesThreshold(): int
    {
        return $this->linesThreshold;
    }

    public function tokensThreshold(): int
    {
        return $this->tokensThreshold;
    }

    public function fuzzy(): bool
    {
        return $this->fuzzy;
    }

    public function verbose(): bool
    {
        return $this->verbose;
    }

    public function help(): bool
    {
        return $this->help;
    }

    public function version(): bool
    {
        return $this->version;
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    public function editDistance(): int
    {
        return $this->editDistance;
    }

    public function headEquality(): int
    {
        return $this->headEquality;
    }
}
