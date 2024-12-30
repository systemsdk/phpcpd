<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD;

use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function array_map;
use function array_slice;
use function current;
use function file;
use function implode;
use function md5;

final class CodeClone
{
    private readonly int $numberOfLines;
    private readonly int $numberOfTokens;
    private readonly string $id;

    /**
     * @var array<string, CodeCloneFile>
     */
    private array $files = [];
    private string $lines = '';

    /**
     * @throws ProcessingResultException
     */
    public function __construct(CodeCloneFile $fileA, CodeCloneFile $fileB, int $numberOfLines, int $numberOfTokens)
    {
        $this->add($fileA);
        $this->add($fileB);

        $this->numberOfLines = $numberOfLines;
        $this->numberOfTokens = $numberOfTokens;
        $this->id = md5($this->lines());
    }

    public function add(CodeCloneFile $file): void
    {
        $id = $file->id();

        if (!isset($this->files[$id])) {
            $this->files[$id] = $file;
        }
    }

    /**
     * @return array<string, CodeCloneFile>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * @throws ProcessingResultException
     */
    public function lines(string $indent = ''): string
    {
        if (!empty($this->lines)) {
            return $this->lines;
        }

        /** @var CodeCloneFile $file */
        $file = current($this->files);
        $fileData = file($file->name());

        if ($fileData === false) {
            throw new ProcessingResultException('Unable to read file: ' . $file->name());
        }

        $this->lines = implode(
            '',
            array_map(
                static function (string $line) use ($indent) {
                    return $indent . $line;
                },
                array_slice(
                    $fileData,
                    $file->startLine() - 1,
                    $this->numberOfLines
                )
            )
        );

        return $this->lines;
    }

    public function numberOfLines(): int
    {
        return $this->numberOfLines;
    }

    public function numberOfTokens(): int
    {
        return $this->numberOfTokens;
    }

    public function id(): string
    {
        return $this->id;
    }
}
