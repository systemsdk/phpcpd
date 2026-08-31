<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Config;

use Systemsdk\PhpCPD\Cli\ArgumentsBuilder;

final class Config
{
    /**
     * @var array<int, string>
     */
    private array $directories = [];

    /**
     * @var array<int, string>
     */
    private array $suffixes = ['.php'];

    /**
     * @var array<int, string>
     */
    private array $exclude = [];

    private ?string $pmdCpdXmlLogfile = null;
    private ?string $jsonLogfile = null;
    private ?string $sarifLogfile = null;
    private bool $fuzzy = false;
    private int $minLines = 5;
    private int $minTokens = 70;
    private string $algorithm = ArgumentsBuilder::ALGORITHM_RABIN_KARP_NAME;
    private int $editDistance = 0;
    private int $headEquality = 10;
    private bool $verbose = false;
    private bool $quiet = false;
    private bool $ignoreNoFiles = false;
    private float $maxPercentage = 0.0;
    private bool $ignoreViolationsOnExit = false;

    private function __construct()
    {
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param array<int, string> $directories
     */
    public function withDirectories(array $directories): self
    {
        $this->directories = $directories;

        return $this;
    }

    /**
     * @param array<int, string> $suffixes
     */
    public function withSuffixes(array $suffixes): self
    {
        $this->suffixes = $suffixes;

        return $this;
    }

    /**
     * @param array<int, string> $exclude
     */
    public function exclude(array $exclude): self
    {
        $this->exclude = $exclude;

        return $this;
    }

    public function withPmdCpdXmlLogfile(string $file): self
    {
        $this->pmdCpdXmlLogfile = $file;

        return $this;
    }

    public function withJsonLogfile(string $file): self
    {
        $this->jsonLogfile = $file;

        return $this;
    }

    public function withSarifLogfile(string $file): self
    {
        $this->sarifLogfile = $file;

        return $this;
    }

    public function withFuzzy(bool $fuzzy = true): self
    {
        $this->fuzzy = $fuzzy;

        return $this;
    }

    public function withMinLines(int $minLines): self
    {
        $this->minLines = $minLines;

        return $this;
    }

    public function withMinTokens(int $minTokens): self
    {
        $this->minTokens = $minTokens;

        return $this;
    }

    public function useRabinKarpAlgorithm(): self
    {
        $this->algorithm = ArgumentsBuilder::ALGORITHM_RABIN_KARP_NAME;

        return $this;
    }

    public function useSuffixTreeAlgorithm(): self
    {
        $this->algorithm = ArgumentsBuilder::ALGORITHM_SUFFIX_TREE_NAME;

        return $this;
    }

    public function withEditDistance(int $editDistance): self
    {
        $this->editDistance = $editDistance;

        return $this;
    }

    public function withHeadEquality(int $headEquality): self
    {
        $this->headEquality = $headEquality;

        return $this;
    }

    public function withVerbose(bool $verbose = true): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function withQuiet(bool $quiet = true): self
    {
        $this->quiet = $quiet;

        return $this;
    }

    public function withIgnoreNoFiles(bool $ignore = true): self
    {
        $this->ignoreNoFiles = $ignore;

        return $this;
    }

    public function withMaxPercentage(float $maxPercentage): self
    {
        $this->maxPercentage = $maxPercentage;

        return $this;
    }

    public function withIgnoreViolationsOnExit(bool $ignore = true): self
    {
        $this->ignoreViolationsOnExit = $ignore;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return array<int, string>
     */
    public function getSuffixes(): array
    {
        return $this->suffixes;
    }

    /**
     * @return array<int, string>
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    public function getPmdCpdXmlLogfile(): ?string
    {
        return $this->pmdCpdXmlLogfile;
    }

    public function getJsonLogfile(): ?string
    {
        return $this->jsonLogfile;
    }

    public function getSarifLogfile(): ?string
    {
        return $this->sarifLogfile;
    }

    public function isFuzzy(): bool
    {
        return $this->fuzzy;
    }

    public function getMinLines(): int
    {
        return $this->minLines;
    }

    public function getMinTokens(): int
    {
        return $this->minTokens;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getEditDistance(): int
    {
        return $this->editDistance;
    }

    public function getHeadEquality(): int
    {
        return $this->headEquality;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    public function isIgnoreNoFiles(): bool
    {
        return $this->ignoreNoFiles;
    }

    public function getMaxPercentage(): float
    {
        return $this->maxPercentage;
    }

    public function isIgnoreViolationsOnExit(): bool
    {
        return $this->ignoreViolationsOnExit;
    }
}
