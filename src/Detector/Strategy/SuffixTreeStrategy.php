<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy;

use Systemsdk\PhpCPD\CodeClone;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTree\AbstractToken;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTree\ApproximateCloneDetectingSuffixTree;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTree\Sentinel;
use Systemsdk\PhpCPD\Detector\Strategy\SuffixTree\Token;
use Systemsdk\PhpCPD\Detector\Traits\ProgressBarTrait;
use Systemsdk\PhpCPD\Exceptions\MissingResultException;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function array_keys;
use function count;
use function file_get_contents;
use function is_array;
use function token_get_all;
use function uniqid;

use const T_ATTRIBUTE;

/**
 * The suffix tree strategy was implemented in PHP for PHPCPD by Olle Härstedt.
 *
 * This PHP implementation is based on the Java implementation archived that is available at
 * https://www.cqse.eu/en/news/blog/conqat-end-of-life/ under the Apache License 2.0.
 *
 * The aforementioned Java implementation is based on the algorithm described in
 * https://dl.acm.org/doi/10.1109/ICSE.2009.5070547. This paper is available at
 * https://www.cqse.eu/fileadmin/content/news/publications/2009-do-code-clones-matter.pdf.
 */
final class SuffixTreeStrategy extends AbstractStrategy
{
    use ProgressBarTrait;

    private const string PROGRESS_BAR_SEARCH_CLONES_TITLE = 'Search for clones';
    private const string PROGRESS_BAR_PROCESS_CLONES_TITLE = 'Clones processing';
    private const string PROGRESS_BAR_POST_PROCESS_DONE_TITLE = 'Post process done';

    /**
     * @var array<int, AbstractToken>
     */
    private array $word = [];

    /**
     * @var array<string, int>
     */
    private array $fileEndPositions = [];

    private ?CodeCloneMap $result = null;

    public function processFile(string $file, CodeCloneMap $result): void
    {
        $content = (string)file_get_contents($file);
        $tokens = token_get_all($content);
        $lastTokenLine = 0;
        $attributeStarted = false;
        $attributeStartedLine = 0;
        $wasSuppressed = false;

        $result->addToNumberOfLines(substr_count($content, "\n"));

        unset($content);

        foreach (array_keys($tokens) as $key) {
            /** @var array{0: int, 1:string, 2:int}|string $token */
            $token = $tokens[$key];

            if (is_array($token)) {
                $tokenLine = (int)$token[2];

                if ($this->guard->isLineSuppressed($file, $tokenLine)) {
                    if (!$wasSuppressed) {
                        // Insert a unique fake barrier token.
                        // It has ID -1 and an absolutely unique value, so the Suffix Tree
                        // will never find a match for it and will stop the clone search at this point.
                        $this->word[] = new Token(
                            -1,
                            'T_SUPPRESSED_BARRIER',
                            $tokenLine,
                            $file,
                            uniqid('barrier_', true)
                        );
                        $wasSuppressed = true;
                    }
                    $lastTokenLine = $tokenLine;

                    continue; // Skip the actual token, keeping it out of the engine
                }

                // Exited the suppressed zone
                $wasSuppressed = false;

                if ($attributeStarted === false && !isset($this->tokensIgnoreList[$token[0]])) {
                    $this->word[] = new Token(
                        $token[0],
                        token_name($token[0]),
                        $tokenLine,
                        $file,
                        $token[1]
                    );
                }

                if ($token[0] === T_ATTRIBUTE) {
                    $attributeStarted = true;
                    $attributeStartedLine = $tokenLine;
                }

                $lastTokenLine = $tokenLine;
            } elseif (
                $attributeStarted === true && $token === ']'
                && (
                    $attributeStartedLine === $lastTokenLine
                    || (($tokens[$key - 1] ?? null) === ')')
                )
            ) {
                $attributeStarted = false;
                $attributeStartedLine = 0;
            }
        }

        $lastIndex = count($this->word) - 1;

        if ($lastIndex >= 0 && $this->word[$lastIndex]->file === $file) {
            $this->fileEndPositions[$file] = $lastIndex;
        }

        $this->result = $result;
    }

    /**
     * @throws MissingResultException
     * @throws ProcessingResultException
     */
    public function postProcess(bool $useProgressBar): void
    {
        if (empty($this->result)) {
            throw new MissingResultException('Missing result');
        }

        $totalSteps = 2;

        if ($useProgressBar) {
            $this->progressBar(0, $totalSteps, self::PROGRESS_BAR_SEARCH_CLONES_TITLE);
        }

        // Sentinel = End of word
        $this->word[] = new Sentinel();

        $cloneInfos = new ApproximateCloneDetectingSuffixTree($this->word)->findClones(
            $this->config->minTokens(),
            $this->config->editDistance(),
            $this->config->headEquality()
        );

        if ($useProgressBar) {
            $this->progressBar(1, $totalSteps, self::PROGRESS_BAR_PROCESS_CLONES_TITLE);
        }

        foreach ($cloneInfos as $cloneInfo) {
            /** @var int[] $others */
            $others = $cloneInfo->otherClones->extractFirstList();

            // Get the exact boundaries of the Original (Head) with O(1) protection against out-of-bounds
            $cloneLength = $this->processCloneLength($cloneInfo->position, $cloneInfo->length, $cloneInfo->token->file);
            $headLastToken = $this->getLastToken($cloneInfo->position, $cloneLength);
            $headLines = $headLastToken->line + 1 - $cloneInfo->token->line;

            // If the clone size meets our limits
            if ($headLines >= $this->config->minLines()) {
                // Add all copies to the result
                for ($j = 0, $count = count($others); $j < $count; $j++) {
                    $otherToken = $this->word[$others[$j]];

                    // Calculate exact boundaries for each copy
                    $otherCloneLength = $this->processCloneLength($others[$j], $cloneLength, $otherToken->file);
                    $otherLastToken = $this->getLastToken($others[$j], $otherCloneLength);
                    $otherLines = $otherLastToken->line + 1 - $otherToken->line;

                    /** @phpstan-ignore method.nonObject */
                    $this->result->add(
                        new CodeClone(
                            new CodeCloneFile(
                                $cloneInfo->token->file,
                                $cloneInfo->token->line,
                                $cloneInfo->token->line + $headLines
                            ),
                            new CodeCloneFile($otherToken->file, $otherToken->line, $otherToken->line + $otherLines),
                            $headLines,
                            $cloneLength
                        )
                    );
                }
            }
        }

        if ($useProgressBar) {
            $this->progressBar(2, $totalSteps, self::PROGRESS_BAR_POST_PROCESS_DONE_TITLE);
        }
    }

    private function processCloneLength(int $position, int $cloneLength, string $file): int
    {
        if (!isset($this->fileEndPositions[$file])) {
            return $cloneLength;
        }

        $maxAllowedLength = $this->fileEndPositions[$file] - $position + 1;

        if ($cloneLength > $maxAllowedLength) {
            return $maxAllowedLength;
        }

        return $cloneLength;
    }

    private function getLastToken(int $position, int $cloneLength): AbstractToken
    {
        $lastToken = $this->word[$position + $cloneLength - 1];
        // If we stumbled upon the Sentinel, rewind one step.
        if ($lastToken instanceof Sentinel) {
            $lastToken = $this->word[$position + $cloneLength - 2];
        }

        return $lastToken;
    }
}
