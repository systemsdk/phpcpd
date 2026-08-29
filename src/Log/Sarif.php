<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use JsonException;
use Systemsdk\PhpCPD\Cli\Application;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function array_shift;
use function array_values;
use function error_get_last;
use function fclose;
use function fopen;
use function fwrite;
use function getcwd;
use function json_encode;
use function preg_match;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Generates a SARIF (Static Analysis Results Interchange Format) 2.1.0 report.
 * Uses a streaming approach to ensure O(1) memory consumption.
 */
final class Sarif
{
    /**
     * Bitmask for json_encode to ensure safe and optimized output.
     */
    private const int ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * The official SARIF schema URI.
     */
    private const string SCHEMA_URI = 'https://json.schemastore.org/sarif-2.1.0.json';

    /**
     * A unique rule ID for code duplication.
     */
    private const string RULE_ID = 'CPD0001';

    /**
     * Cached current working directory for fast relative path resolution.
     */
    private readonly string $cwd;

    public function __construct(
        private readonly string $filename
    ) {
        $cwd = getcwd();
        $this->cwd = $cwd !== false ? str_replace('\\', '/', $cwd) . '/' : '';
    }

    /**
     * Processes clones and writes the SARIF report to the file.
     *
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
            $this->writeHeader($fileResource);
            $this->writeResults($fileResource, $clones);
            $this->writeFooter($fileResource);
        } catch (JsonException $exception) {
            throw new LoggerException(
                'SARIF encoding error: ' . $exception->getMessage(),
                (int)$exception->getCode(),
                $exception
            );
        } finally {
            fclose($fileResource);
        }
    }

    /**
     * Writes the SARIF document structure up to the results array.
     *
     * @param resource $fileResource
     *
     * @throws JsonException
     */
    private function writeHeader($fileResource): void
    {
        $header = [
            '$schema' => self::SCHEMA_URI,
            'version' => '2.1.0',
            'runs' => [
                [
                    'automationDetails' => [
                        'id' => 'phpcpd-run',
                    ],
                    'tool' => [
                        'driver' => [
                            'name' => 'phpcpd',
                            'fullName' => 'Copy/Paste Detector (phpcpd)',
                            'semanticVersion' => Application::VERSION,
                            'informationUri' => 'https://github.com/systemsdk/phpcpd',
                            'rules' => [
                                [
                                    'id' => self::RULE_ID,
                                    'name' => 'DuplicateCode',
                                    'helpUri' => 'https://github.com/systemsdk/phpcpd',
                                    'shortDescription' => [
                                        'text' => 'Code duplication detected.',
                                    ],
                                    'fullDescription' => [
                                        'text' => 'Identical or structurally similar code blocks were found in multiple'
                                            . ' locations.',
                                    ],
                                    'help' => [
                                        'text' => 'Consider extracting the duplicated code into a shared method, class,'
                                            . ' or trait to improve maintainability and prevent logic divergence.',
                                        'markdown' => 'Consider extracting the duplicated code into a shared method,'
                                            . ' class, or trait to improve maintainability and prevent logic'
                                            . ' divergence.',
                                    ],
                                    'defaultConfiguration' => [
                                        'level' => 'warning',
                                    ],
                                    'messageStrings' => [
                                        'default' => [
                                            'text' => 'Found \'{0}\' lines of \'{1}\' duplicated code.',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // We prepare to stream the results array
                    'results' => [],
                ],
            ],
        ];

        $json = json_encode($header, self::ENCODE_FLAGS);
        $streamedHeader = substr($json, 0, -4);

        fwrite($fileResource, $streamedHeader);
    }

    /**
     * Streams individual clone results into the JSON structure.
     *
     * @param resource $fileResource
     *
     * @throws JsonException
     */
    private function writeResults($fileResource, CodeCloneMap $clones): void
    {
        $isFirst = true;

        foreach ($clones as $clone) {
            if (!$isFirst) {
                fwrite($fileResource, ',');
            }
            $isFirst = false;

            $filesList = array_values($clone->files());
            /** @var CodeCloneFile $primaryFile */
            $primaryFile = array_shift($filesList);

            $level = $clone->isExact() ? 'note' : 'warning';
            $cloneType = $clone->isExact() ? 'exact' : 'inconsistent';
            $linesCount = (string)$clone->numberOfLines();

            $result = [
                'ruleId' => self::RULE_ID,
                'level' => $level,
                // SARIF2002 vs ADO1015/GH1015: Providing both 'id'/'arguments' and 'text' satisfies all validators
                'message' => [
                    'id' => 'default',
                    'arguments' => [$linesCount, $cloneType],
                    'text' => sprintf("Found '%s' lines of '%s' duplicated code.", $linesCount, $cloneType),
                ],
                // ADO1015/GH1015: Partial fingerprints allow platforms to track this issue across commits
                'partialFingerprints' => [
                    'primaryLocationLineHash' => $clone->id(),
                ],
                'locations' => [
                    [
                        'physicalLocation' => [
                            'artifactLocation' => [
                                'uri' => $this->formatUri($primaryFile->name()),
                            ],
                            'region' => [
                                'startLine' => $primaryFile->startLine(),
                                'startColumn' => 1,
                                'endLine' => $primaryFile->endLine(),
                                // SARIF2010: Embeds the actual code snippet in the report
                                'snippet' => [
                                    'text' => $clone->lines(),
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            if (!empty($filesList)) {
                $result['relatedLocations'] = [];
                foreach ($filesList as $relatedFile) {
                    $result['relatedLocations'][] = [
                        'physicalLocation' => [
                            'artifactLocation' => [
                                'uri' => $this->formatUri($relatedFile->name()),
                            ],
                            'region' => [
                                'startLine' => $relatedFile->startLine(),
                                'startColumn' => 1,
                                'endLine' => $relatedFile->endLine(),
                                'snippet' => [
                                    'text' => $clone->lines(),
                                ],
                            ],
                        ],
                    ];
                }
            }

            fwrite($fileResource, json_encode($result, self::ENCODE_FLAGS));
        }
    }

    /**
     * Closes the streamed JSON structures.
     *
     * @param resource $fileResource
     */
    private function writeFooter($fileResource): void
    {
        fwrite($fileResource, ']}]}');
    }

    /**
     * Formats the file path into a valid SARIF URI.
     */
    private function formatUri(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);

        if ($this->cwd !== '' && str_starts_with($normalizedPath, $this->cwd)) {
            $normalizedPath = substr($normalizedPath, strlen($this->cwd));
        }

        if (str_starts_with($normalizedPath, '/')) {
            return 'file://' . $normalizedPath;
        }

        if (preg_match('#^[a-zA-Z]:/#', $normalizedPath)) {
            return 'file:///' . $normalizedPath;
        }

        return $normalizedPath;
    }
}
