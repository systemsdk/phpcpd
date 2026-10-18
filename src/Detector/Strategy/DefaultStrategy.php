<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy;

use Systemsdk\PhpCPD\CodeClone;
use Systemsdk\PhpCPD\CodeCloneFile;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function array_keys;
use function count;
use function crc32;
use function file_get_contents;
use function is_array;
use function ord;
use function pack;
use function substr_count;
use function token_get_all;
use function uniqid;

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
        $suppressedRanges = $this->guard->getSuppressedRanges($file);
        $hasSuppressed = $suppressedRanges !== [];

        foreach ($suppressedRanges as $range) {
            $result->addSuppressed($range['end'] - $range['start'] + 1);
        }
        $buffer = (string)file_get_contents($file);
        /** @var array<int, int> $currentTokenPositions */
        $currentTokenPositions = [];
        /** @var array<int, int> $currentTokenRealPositions */
        $currentTokenRealPositions = [];

        /** @var array<int, int> $tokenHashes */
        $tokenHashes = [];

        $tokens = token_get_all($buffer);
        $tokenNr = 0;
        $lastTokenLine = 0;
        $attributeDepth = 0;
        $firstHash = '';
        $firstToken = 0;
        $wasSuppressed = false;

        $result->addToNumberOfLines(substr_count($buffer, "\n"));

        unset($buffer);

        foreach (array_keys($tokens) as $key) {
            /** @var array{0: int, 1:string, 2:int}|string $token */
            $token = $tokens[$key];

            if (is_array($token)) {
                $tokenLine = (int)$token[2];

                // If the current line is protected by #[SuppressCpd]
                if ($hasSuppressed && $this->guard->isLineSuppressed($file, $tokenLine)) {
                    // If we JUST entered the suppressed zone, place a Barrier Wall
                    if (!$wasSuppressed) {
                        if ($tokenNr === 0) {
                            $currentTokenPositions[$tokenNr] = 0;
                        } else {
                            $currentTokenPositions[$tokenNr] = $currentTokenPositions[$tokenNr - 1];
                        }

                        $currentTokenRealPositions[$tokenNr] = $tokenLine;

                        // Create a globally unique token (Barrier) to poison any sliding window
                        // that tries to cross this area. This prevents wormhole bugs.
                        $tokenHashes[] = (crc32(uniqid('barrier', true)) ^ 255) & 0x7FFFFFFF;
                        $tokenNr++;
                        $wasSuppressed = true;
                    }

                    $lastTokenLine = $tokenLine;

                    continue; // Skip the actual token, keeping it out of the engine
                }

                // We are in normal code, reset the flag
                $wasSuppressed = false;

                // 2. Handle PHP 8 Attributes entry
                if ($token[0] === T_ATTRIBUTE) {
                    $attributeDepth = 1; // Start depth counting
                    $lastTokenLine = $tokenLine;

                    continue; // Skip the T_ATTRIBUTE token itself
                }

                // 3. Record valid tokens (only if we are NOT inside an attribute)
                if ($attributeDepth === 0 && !isset($this->tokensIgnoreList[$token[0]])) {
                    if ($tokenNr === 0) {
                        $currentTokenPositions[$tokenNr] = $tokenLine - $lastTokenLine;
                    } else {
                        $currentTokenPositions[$tokenNr] = $currentTokenPositions[$tokenNr - 1] + $tokenLine
                            - $lastTokenLine;
                    }

                    $currentTokenRealPositions[$tokenNr++] = $tokenLine;

                    if ($token[0] === T_VARIABLE && $this->config->fuzzy()) {
                        $token[1] = 'variable';
                    }

                    $tokenHashes[] = (crc32($token[1]) ^ ($token[0] & 255)) & 0x7FFFFFFF;
                }

                $lastTokenLine = $tokenLine;
            } else {
                // 4. Handle single-character string tokens (e.g., '+', '*', '{', '[')
                $tokenLine = $lastTokenLine > 0 ? $lastTokenLine : 1;

                if ($hasSuppressed && $this->guard->isLineSuppressed($file, $tokenLine)) {
                    continue;
                }

                if ($attributeDepth > 0) {
                    // This safely calculates the nesting level of arrays inside attributes
                    if ($token === '[') {
                        $attributeDepth++;
                    } elseif ($token === ']') {
                        $attributeDepth--;
                    }
                } else {
                    // FIX: Add single-character operators to the token stream for Rabin-Karp hashing
                    if ($tokenNr === 0) {
                        $currentTokenPositions[$tokenNr] = 0;
                    } else {
                        $currentTokenPositions[$tokenNr] = $currentTokenPositions[$tokenNr - 1];
                    }

                    $currentTokenRealPositions[$tokenNr++] = $tokenLine;

                    // Use the ASCII value of the character as its token type
                    $tokenHashes[] = (crc32($token) ^ (ord($token) & 255)) & 0x7FFFFFFF;
                }
            }
        }

        $count = count($currentTokenPositions);
        $minTokens = $this->config->minTokens();

        if ($count < $minTokens) {
            return;
        }

        // ---------------------------------------------------------
        // DOUBLE POLYNOMIAL ROLLING HASH SETUP (O(1) sliding window)
        // ---------------------------------------------------------
        $base1 = 31;
        $mod1 = 2147483647; // 2^31 - 1
        $base2 = 37;
        $mod2 = 2147483629; // 2^31 - 19

        $power1 = 1;
        $power2 = 1;
        for ($i = 0; $i < $minTokens - 1; $i++) {
            $power1 = ($power1 * $base1) % $mod1;
            $power2 = ($power2 * $base2) % $mod2;
        }

        $hash1 = 0;
        $hash2 = 0;

        // Initial window hash computation
        for ($i = 0; $i < $minTokens; $i++) {
            $hash1 = (($hash1 * $base1) % $mod1 + ($tokenHashes[$i] % $mod1)) % $mod1;
            $hash2 = (($hash2 * $base2) % $mod2 + ($tokenHashes[$i] % $mod2)) % $mod2;
        }

        $firstLine = 0;
        $firstRealLine = 0;
        $found = false;
        $tokenNr = 0;

        while ($tokenNr <= $count - $minTokens) {
            $line = $currentTokenPositions[$tokenNr];
            $realLine = $currentTokenRealPositions[$tokenNr];

            $hash = pack('NN', $hash1, $hash2);

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

            // ROLLING THE HASH: O(1) update for the next iteration
            if ($tokenNr < $count - $minTokens) {
                $outToken = $tokenHashes[$tokenNr];
                $inToken = $tokenHashes[$tokenNr + $minTokens];

                // Roll Hash 1: Subtract old token, shift left, add new token
                $sub1 = ($outToken % $mod1 * $power1) % $mod1;
                $hash1 = ($hash1 - $sub1) % $mod1;
                if ($hash1 < 0) {
                    $hash1 += $mod1;
                }
                $hash1 = ($hash1 * $base1) % $mod1;
                $hash1 = ($hash1 + ($inToken % $mod1)) % $mod1;

                // Roll Hash 2: Subtract old token, shift left, add new token
                $sub2 = ($outToken % $mod2 * $power2) % $mod2;
                $hash2 = ($hash2 - $sub2) % $mod2;
                if ($hash2 < 0) {
                    $hash2 += $mod2;
                }
                $hash2 = ($hash2 * $base2) % $mod2;
                $hash2 = ($hash2 + ($inToken % $mod2)) % $mod2;
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
                    new CodeCloneFile($fileA, $firstLineA, $firstLineA + $realNumLines),
                    new CodeCloneFile($file, $firstRealLine, $firstRealLine + $realNumLines),
                    $realNumLines,
                    $lastToken + 1 - $firstToken
                )
            );
        }
    }
}
