<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy;

use Systemsdk\PhpCPD\CodeClone;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function array_key_exists;
use function array_keys;
use function chr;
use function count;
use function crc32;
use function file_get_contents;
use function is_array;
use function md5;
use function pack;
use function substr;
use function substr_count;
use function token_get_all;

use const T_ATTRIBUTE;
use const T_VARIABLE;

/**
 *  This is a Rabin-Karp with an additional normalization steps before the hashing happens.
 *
 *  1. Tokenization
 *  2. Deletion of logic neutral tokens like T_CLOSE_TAG;T_COMMENT; T_DOC_COMMENT; T_INLINE_HTML; T_NS_SEPARATOR;
 *      T_OPEN_TAG; T_OPEN_TAG_WITH_ECHO; T_USE; T_WHITESPACE;
 *  3. If needed deletion of variable names
 *  4. Normalization of token + value using crc32
 *  5. Now the classic Rabin-Karp hashing takes place
 */
final class DefaultStrategy extends AbstractStrategy
{
    /**
     * @var array<string, array{0: string, 1: int}>
     */
    private array $hashes = [];

    /**
     * @throws ProcessingResultException
     */
    public function processFile(string $file, CodeCloneMap $result): void
    {
        $buffer = (string)file_get_contents($file);
        /** @var array<int, int> $currentTokenPositions */
        $currentTokenPositions = [];
        /** @var array<int, int> $currentTokenRealPositions */
        $currentTokenRealPositions = [];
        $currentSignature = '';
        $tokens = token_get_all($buffer);
        $tokenNr = 0;
        $lastTokenLine = 0;
        $attributeStarted = false;
        $attributeStartedLine = 0;
        $firstHash = '';
        $firstToken = 0;

        $result->addToNumberOfLines(substr_count($buffer, "\n"));

        unset($buffer);

        foreach (array_keys($tokens) as $key) {
            /** @var array{0: int, 1:string, 2:int}|string $token */
            $token = $tokens[$key];

            if (is_array($token)) {
                if ($attributeStarted === false && !isset($this->tokensIgnoreList[$token[0]])) {
                    if ($tokenNr === 0) {
                        $currentTokenPositions[$tokenNr] = $token[2] - $lastTokenLine;
                    } else {
                        $currentTokenPositions[$tokenNr] = $currentTokenPositions[$tokenNr - 1] + $token[2]
                            - $lastTokenLine;
                    }

                    $currentTokenRealPositions[$tokenNr++] = (int)$token[2];

                    if ($token[0] === T_VARIABLE && $this->config->fuzzy()) {
                        $token[1] = 'variable';
                    }

                    $currentSignature .= chr($token[0] & 255) . pack('N*', crc32($token[1]));
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

        $count = count($currentTokenPositions);
        $firstLine = 0;
        $firstRealLine = 0;
        $found = false;
        $tokenNr = 0;

        while ($tokenNr <= $count - $this->config->minTokens()) {
            $line = $currentTokenPositions[$tokenNr];
            $realLine = $currentTokenRealPositions[$tokenNr];

            $hash = substr(md5(substr($currentSignature, $tokenNr * 5, $this->config->minTokens() * 5), true), 0, 8);

            if (isset($this->hashes[$hash])) {
                $found = true;

                if ($firstLine === 0) {
                    $firstLine = $line;
                    $firstRealLine = $realLine;
                    $firstHash = $hash;
                    $firstToken = $tokenNr;
                }
            } else {
                if ($found) {
                    $this->processResult(
                        $result,
                        $firstHash,
                        $tokenNr,
                        $currentTokenPositions,
                        $currentTokenRealPositions,
                        $firstLine,
                        $firstRealLine,
                        $file,
                        $firstToken
                    );
                    $found = false;
                    $firstLine = 0;
                }

                $this->hashes[$hash] = [$file, $realLine];
            }

            $tokenNr++;
        }

        if ($found) {
            $this->processResult(
                $result,
                $firstHash,
                $tokenNr,
                $currentTokenPositions,
                $currentTokenRealPositions,
                $firstLine,
                $firstRealLine,
                $file,
                $firstToken
            );
        }
    }

    /**
     * @param array<int, int> $currentTokenPositions
     * @param array<int, int> $currentTokenRealPositions
     *
     * @throws ProcessingResultException
     */
    private function processResult(
        CodeCloneMap $result,
        string $firstHash,
        int $tokenNr,
        array $currentTokenPositions,
        array $currentTokenRealPositions,
        int $firstLine,
        int $firstRealLine,
        string $file,
        int $firstToken
    ): void {
        [$fileA, $firstLineA] = $this->hashes[$firstHash];
        $lastToken = ($tokenNr - 1) + $this->config->minTokens() - 1;
        $lastLine = $currentTokenPositions[$lastToken];
        $lastRealLine = $currentTokenRealPositions[$lastToken];
        $numLines = $lastLine + 1 - $firstLine;
        $realNumLines = $lastRealLine + 1 - $firstRealLine;

        if (($fileA !== $file || $firstLineA !== $firstRealLine) && $numLines >= $this->config->minLines()) {
            $result->add(
                new CodeClone(
                    new CodeCloneFile($fileA, $firstLineA),
                    new CodeCloneFile($file, $firstRealLine),
                    $realNumLines,
                    $lastToken + 1 - $firstToken
                )
            );
        }
    }
}
