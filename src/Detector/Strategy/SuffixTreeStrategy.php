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
use Systemsdk\PhpCPD\Exceptions\MissingResultException;

use function array_key_exists;
use function array_keys;
use function file_get_contents;
use function is_array;
use function token_get_all;

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
    /**
     * @var array<int, AbstractToken>
     */
    private array $word = [];

    /**
     * @var array<string, int>
     */
    private array $fileTokens = [];

    private ?CodeCloneMap $result = null;

    public function processFile(string $file, CodeCloneMap $result): void
    {
        $content = (string)file_get_contents($file);
        $tokens = token_get_all($content);
        $lastTokenLine = 0;
        $attributeStarted = false;
        $attributeStartedLine = 0;
        $this->fileTokens[$file] = 0;

        $result->addToNumberOfLines(substr_count($content, "\n"));

        unset($content);

        foreach (array_keys($tokens) as $key) {
            /** @var array{0: int, 1:string, 2:int}|string $token */
            $token = $tokens[$key];

            if (is_array($token)) {
                if ($attributeStarted === false && !isset($this->tokensIgnoreList[$token[0]])) {
                    $this->word[] = new Token(
                        $token[0],
                        token_name($token[0]),
                        $token[2],
                        $file,
                        $token[1]
                    );
                    $this->fileTokens[$file]++;
                }

                if ($token[0] === T_ATTRIBUTE) {
                    $attributeStarted = true;
                    $attributeStartedLine = $token[2];
                }

                $lastTokenLine = $token[2];
            } elseif (
                $attributeStarted === true && $token === ']'
                && (
                    $attributeStartedLine === $lastTokenLine
                    || (array_key_exists($key - 1, $tokens) && $tokens[$key - 1] === ')')
                )
            ) {
                $attributeStarted = false;
                $attributeStartedLine = 0;
            }
        }

        $this->result = $result;
    }

    /**
     * @throws MissingResultException
     */
    public function postProcess(): void
    {
        if (empty($this->result)) {
            throw new MissingResultException('Missing result');
        }

        // Sentinel = End of word
        $this->word[] = new Sentinel();

        $cloneInfos = (new ApproximateCloneDetectingSuffixTree($this->word))->findClones(
            $this->config->minTokens(),
            $this->config->editDistance(),
            $this->config->headEquality()
        );

        foreach ($cloneInfos as $cloneInfo) {
            /** @var int[] $others */
            $others = $cloneInfo->otherClones->extractFirstList();
            $cloneLength = $this->processCloneLength($cloneInfo->length, $cloneInfo->token->file);
            $cloneInfoLastToken = $this->getLastToken($cloneInfo->position, $cloneLength);
            $lines = $cloneInfoLastToken->line + 1 - $cloneInfo->token->line;

            if ($lines >= $this->config->minLines()) {
                for ($j = 0, $count = count($others); $j < $count; $j++) {
                    $otherToken = $this->word[$others[$j]];
                    $otherCloneLength = $this->processCloneLength($cloneLength, $otherToken->file);
                    $otherLastToken = $this->getLastToken($others[$j], $otherCloneLength);

                    $this->result->add(
                        new CodeClone(
                            new CodeCloneFile(
                                $cloneInfo->token->file,
                                $cloneInfo->token->line,
                                $cloneInfo->token->line + $lines
                            ),
                            new CodeCloneFile($otherToken->file, $otherToken->line, $otherLastToken->line + 1),
                            $lines,
                            $cloneLength
                        )
                    );
                }
            }
        }
    }

    private function processCloneLength(int $cloneLength, string $file): int
    {
        if ($cloneLength > $this->fileTokens[$file]) {
            $cloneLength = $this->fileTokens[$file];
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
