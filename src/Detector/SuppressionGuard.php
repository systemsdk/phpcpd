<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector;

use function count;
use function file_get_contents;
use function is_array;
use function token_get_all;

use const T_ATTRIBUTE;
use const T_CURLY_OPEN;
use const T_DOLLAR_OPEN_CURLY_BRACES;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_STRING;

final class SuppressionGuard
{
    /**
     * @var array<string, array<int, array{start: int, end: int}>>
     */
    private array $cache = [];

    public function isLineSuppressed(string $file, int $line): bool
    {
        return array_any(
            $this->getSuppressedRanges($file),
            static fn (array $range): bool => $line >= $range['start'] && $line <= $range['end']
        );
    }

    /**
     * Parses the file to find all #[SuppressCpd] attribute ranges.
     *
     * @return array<int, array{start: int, end: int}>
     */
    private function getSuppressedRanges(string $file): array
    {
        if (isset($this->cache[$file])) {
            return $this->cache[$file];
        }

        $this->cache[$file] = [];
        $content = @file_get_contents($file);

        if ($content === false) {
            return [];
        }

        $tokens = token_get_all($content);
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            // 1. Found the start of the attribute "#["
            if (!is_array($token) || $token[0] !== T_ATTRIBUTE) {
                continue;
            }

            // 2. Scan the attribute body until the VERY LAST "]" bracket
            $hasSuppressName = false;
            $nestingLevel = 0; // Square brackets nesting level inside the attribute
            $j = $i + 1;

            while ($j < $count) {
                $t = $tokens[$j];

                // Check for SuppressCpd name (handling simple names, FQCN, and aliased names)
                if (
                    is_array($t)
                    && (
                        $t[0] === T_STRING
                        || $t[0] === T_NAME_QUALIFIED
                        || $t[0] === T_NAME_FULLY_QUALIFIED
                    )
                    && str_contains($t[1], 'SuppressCpd')
                ) {
                    $hasSuppressName = true;
                }

                // Handle nested brackets [ ... ]
                if ($t === '[') {
                    $nestingLevel++;
                } elseif ($t === ']') {
                    if ($nestingLevel > 0) {
                        // Closing bracket of a nested array inside the attribute
                        // Example: #[Route(['path'])] - we are here
                        $nestingLevel--;
                    } else {
                        // Final closing bracket of the attribute itself
                        // Example: #[... , SuppressCpd] - we are here
                        break;
                    }
                } elseif ($t === ';' || $t === '{' || (is_array($t) && $t[0] === T_CURLY_OPEN)) {
                    break; // Safety guard: attribute didn't close properly before code started
                }

                $j++;
            }

            if (!$hasSuppressName) {
                continue;
            }

            // 3. Attribute found! Look for the code block (scope) it applies to
            // Start searching immediately after the attribute's closing bracket ($j)
            $braceBalance = 0;
            $parenBalance = 0; // Track parentheses in the method signature
            $foundOpeningBrace = false;
            $attrLine = (int)$token[2];

            for ($k = $j + 1; $k < $count; $k++) {
                $t = $tokens[$k];

                if (!$foundOpeningBrace) {
                    // Track parentheses to safely ignore anonymous classes defined in default arguments
                    if ($t === '(') {
                        $parenBalance++;

                        continue;
                    }
                    if ($t === ')') {
                        $parenBalance--;

                        continue;
                    }

                    // Support for properties and abstract methods (ending with ";")
                    // Guard: react to ';' only if we are not inside a method signature's parentheses
                    if ($t === ';' && $braceBalance === 0 && $parenBalance === 0) {
                        $this->saveRange($file, $attrLine, $this->findScopeEnd($k, $j, $tokens));
                        break;
                    }

                    // Open the block only if we are NOT inside a method signature ($parenBalance === 0)
                    if (
                        $parenBalance === 0
                        && (
                            $t === '{'
                            || (is_array($t) && ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES))
                        )
                    ) {
                        $foundOpeningBrace = true;
                        $braceBalance++;
                    }

                    continue;
                }

                // Inside the block {...}
                if ($t === '{' || (is_array($t) && ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES))) {
                    $braceBalance++;
                } elseif ($t === '}') {
                    $braceBalance--;

                    if ($braceBalance === 0) {
                        // Block closed
                        $this->saveRange($file, $attrLine, $this->findScopeEnd($k, $j, $tokens));
                        break;
                    }
                }
            }
        }

        return $this->cache[$file];
    }

    /**
     * Helper to find the end line number of the scope using lookbehind.
     *
     * @param array<int, string|array{0: int, 1: string, 2: int}> $tokens
     */
    private function findScopeEnd(int $currentIndex, int $limitIndex, array $tokens): int
    {
        // Go backwards from the current token to find the last token with a line number
        for ($z = $currentIndex; $z > $limitIndex; $z--) {
            if (isset($tokens[$z]) && is_array($tokens[$z])) {
                return (int)$tokens[$z][2];
            }
        }

        // Fallback
        return isset($tokens[$currentIndex]) && is_array($tokens[$currentIndex])
            ? (int)$tokens[$currentIndex][2]
            : 0;
    }

    private function saveRange(string $file, int $start, int $end): void
    {
        if ($end > 0) {
            $this->cache[$file][] = [
                'start' => $start,
                'end' => $end,
            ];
        }
    }
}
