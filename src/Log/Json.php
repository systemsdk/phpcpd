<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use Systemsdk\PhpCPD\Cli\Application;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function error_get_last;
use function fclose;
use function fopen;
use function fwrite;
use function json_encode;
use function sprintf;
use function str_replace;
use function substr;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Json
{
    /**
     * Bitmask for json_encode to ensure safe and optimized output.
     */
    private const int ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(
        private readonly string $filename
    ) {
    }

    /**
     * @throws LoggerException
     */
    public function processClones(CodeCloneMap $clones): void
    {
        $fileResource = fopen($this->filename, 'wb');

        if ($fileResource === false) {
            $error = error_get_last();
            $message = $error !== null ? $error['message'] : 'Unknown error';

            throw new LoggerException(
                sprintf('Could not open file "%s" for writing: %s', $this->filename, $message)
            );
        }

        try {
            $this->writeHeader($fileResource, $clones);
            $this->writeClones($fileResource, $clones);
            $this->writeFooter($fileResource);
        } catch (JsonException $exception) {
            throw new LoggerException(
                'JSON encoding error: ' . $exception->getMessage(),
                (int)$exception->getCode(),
                $exception
            );
        } finally {
            fclose($fileResource);
        }
    }

    /**
     * @param resource $fileResource
     *
     * @throws JsonException
     */
    private function writeHeader($fileResource, CodeCloneMap $clones): void
    {
        $header = [
            'metadata' => [
                'tool' => 'phpcpd',
                'version' => Application::VERSION,
                'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::ATOM),
            ],
            'summary' => [
                'totalClones' => $clones->count(),
                'duplicatedLines' => $clones->numberOfDuplicatedLines(),
                'totalLines' => $clones->numberOfLines(),
                // Remove the % symbol from the string and cast to float for JSON schema purity
                'percentage' => (float)str_replace('%', '', $clones->percentage()),
                'filesWithClones' => $clones->numberOfFilesWithClones(),
                'averageCloneSize' => $clones->averageSize(),
                'largestCloneSize' => $clones->largestSize(),
                'suppressedClones' => $clones->getSuppressedClones(),
                'suppressedLines' => $clones->getSuppressedLines(),
            ],
        ];

        $json = json_encode($header, self::ENCODE_FLAGS);

        // Remove the closing bracket '}' of the root object and open the clones array
        $streamedHeader = substr($json, 0, -1) . ',"clones":[';

        fwrite($fileResource, $streamedHeader);
    }

    /**
     * @param resource $fileResource
     *
     * @throws JsonException
     */
    private function writeClones($fileResource, CodeCloneMap $clones): void
    {
        $isFirst = true;

        foreach ($clones as $clone) {
            if (!$isFirst) {
                fwrite($fileResource, ',');
            }
            $isFirst = false;

            $files = [];
            foreach ($clone->files() as $file) {
                $files[] = [
                    'path' => $file->name(),
                    'startLine' => $file->startLine(),
                    'endLine' => $file->endLine(),
                ];
            }

            $cloneData = [
                'lines' => $clone->numberOfLines(),
                'tokens' => $clone->numberOfTokens(),
                'exact' => $clone->isExact(),
                'files' => $files,
                'codefragment' => $clone->lines(),
            ];

            fwrite($fileResource, json_encode($cloneData, self::ENCODE_FLAGS));
        }
    }

    /**
     * @param resource $fileResource
     */
    private function writeFooter($fileResource): void
    {
        fwrite($fileResource, ']}');
    }
}
