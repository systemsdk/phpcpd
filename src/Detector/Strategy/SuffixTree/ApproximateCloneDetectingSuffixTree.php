<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

use Systemsdk\PhpCPD\Exceptions\ProcessingResultException;

use function array_fill;
use function array_values;
use function max;
use function min;
use function sprintf;
use function usort;

/**
 * An extension of the suffix tree adding an algorithm for finding approximate clones, i.e. substrings which are similar
 */
class ApproximateCloneDetectingSuffixTree extends SuffixTree
{
    /**
     * This is the distance between two entries in the {@link #cloneInfos} map.
     */
    private const int INDEX_SPREAD = 10;

    /**
     * The maximal length of a clone. This influences the size of the (quadratic) {@link #edBuffer}.
     */
    private const int MAX_LENGTH = 1024;

    /**
     * The minimal length of clones to return.
     */
    protected int $minLength = 70;

    /**
     * The number of leaves reachable from the given node (1 for leaves).
     *
     * @var int[]
     * */
    private array $leafCount;

    /**
     * This map stores for each position the relevant clone infos.
     *
     * @var array<CloneInfo[]>
     */
    private array $cloneInfos = [];

    /**
     * Buffer used for calculating edit distance.
     *
     * @var array<int[]>
     */
    private array $edBuffer;

    /**
     * Number of units that must be equal at the start of a clone.
     */
    private int $headEquality = 10;

    /**
     * Create a new suffix tree from a given word. The word given as parameter is used internally and should
     * not be modified anymore, so copy it before if required.
     * <p>
     * This only word correctly if the given word is closed using a sentinel character.
     *
     * @param AbstractToken[] $word List of tokens to analyze
     */
    public function __construct(array $word)
    {
        parent::__construct($word);

        $arr = array_fill(0, self::MAX_LENGTH, 0);
        $this->edBuffer = array_fill(0, self::MAX_LENGTH, $arr);
        $this->ensureChildLists();
        $this->leafCount = array_fill(0, $this->numNodes, 0);
        $this->initLeafCount(0);
    }

    /**
     * Finds all clones in the string (List) used in the constructor.
     *
     * @param int $minLength the minimal length of a clone in tokens (not lines)
     * @param int $maxErrors the maximal number of errors/gaps allowed
     * @param int $headEquality the number of elements which have to be the same at the beginning of a clone
     *
     * @throws ProcessingResultException
     *
     * @return CloneInfo[]
     */
    public function findClones(int $minLength, int $maxErrors, int $headEquality): array
    {
        $this->minLength = $minLength;
        $this->headEquality = $headEquality;
        $this->cloneInfos = [];
        $wordCount = $this->wordCount;

        for ($i = 0; $i < $wordCount; $i++) {
            // FIX: Early cutoff optimization. If the remaining tokens in the stream are fewer than the required
            // minimum clone length, it is mathematically impossible to find a valid clone. Stop processing the tail.
            if (($wordCount - $i) < $minLength) {
                break;
            }

            // Do quick start, as first character has to match anyway.
            $node = $this->nextNode->get(0, $this->word[$i]);

            if ($node < 0 || $this->leafCount[$node] <= 1) {
                continue;
            }

            // we know that we have an exact match of at least 'length'
            // characters, as the word itself is part of the suffix tree.
            $length = $this->nodeWordEnd[$node] - $this->nodeWordBegin[$node];
            $numReported = 0;

            for ($e = $this->nodeChildFirst[$node]; $e >= 0; $e = $this->nodeChildNext[$e]) {
                if ($this->matchWord($i, $i + $length, $this->nodeChildNode[$e], $length, $maxErrors)) {
                    $numReported++;
                }
            }

            if ($length >= $this->minLength && $numReported !== 1) {
                $this->reportClone($i, $i + $length, $node, $length, $length);
            }
        }

        $map = [];

        for ($index = 0; $index <= $wordCount; $index++) {
            /** @var CloneInfo[]|null $existingClones */
            $existingClones = $this->cloneInfos[$index] ?? null;

            if ($existingClones !== null) {
                foreach ($existingClones as $ci) {
                    // length = number of tokens
                    if ($ci->length >= $minLength) {
                        $mapKey = $ci->token->file . ':' . $ci->token->line;
                        $previousCi = $map[$mapKey] ?? null;

                        if ($previousCi === null || $ci->length > $previousCi->length) {
                            $map[$mapKey] = $ci;
                        }
                    }
                }
            }
        }

        /** @var CloneInfo[] $values */
        $values = array_values($map);
        usort($values, static function (CloneInfo $a, CloneInfo $b): int {
            return $b->length - $a->length;
        });

        return $values;
    }

    /**
     * This should return true, if the provided character is not allowed to match with anything
     * else (e.g. is a sentinel).
     */
    protected function mayNotMatch(AbstractToken $token): bool
    {
        return $token instanceof Sentinel;
    }

    /**
     * This method is called whenever the {@link #MAX_LENGTH} is to small and hence the {@link #edBuffer} was not
     * large enough. This may cause that a really large clone is reported in multiple chunks of size{@link #MAX_LENGTH}
     * and potentially minor parts of such a clone might be lost.
     *
     * @throws ProcessingResultException
     */
    protected function reportBufferShortage(int $leafStart, int $leafLength): void
    {
        throw new ProcessingResultException(sprintf('Encountered buffer shortage: %d %d', $leafStart, $leafLength));
    }

    /**
     * Initializes the {@link #leafCount} array which given for each node the number of leaves reachable from it
     * (where leaves obtain a value of 1).
     */
    private function initLeafCount(int $node): void
    {
        $count = 0;

        for ($e = $this->nodeChildFirst[$node]; $e >= 0; $e = $this->nodeChildNext[$e]) {
            $child = $this->nodeChildNode[$e];

            if ($this->nodeChildFirst[$child] < 0) {
                $this->leafCount[$child] = 1;
                $count++;
            } else {
                $this->initLeafCount($child);
                $count += $this->leafCount[$child];
            }
        }

        $this->leafCount[$node] = $count === 0 ? 1 : $count;
    }

    /**
     * Performs the approximative matching between the input word and the tree.
     *
     * @param int $wordStart the start position of the currently matched word (position in the input word)
     * @param int $wordPosition the current position along the input word
     * @param int $node the node we are currently at (i.e. the edge leading to this node is relevant to us).
     * @param int $nodeWordLength the length of the word found along the nodes (this may be different from the length
     *                            along the input word due to gaps)
     * @param int $maxErrors the number of errors still allowed
     *
     * @throws ProcessingResultException
     *
     * @return bool whether some clone was reported
     */
    private function matchWord(int $wordStart, int $wordPosition, int $node, int $nodeWordLength, int $maxErrors): bool
    {
        // We are aware that this method is longer than desirable for code
        // reading. However, we currently do not see a refactoring that has a
        // sensible cost-benefit ratio. Suggestions are welcome!

        // self match?
        if ($this->leafCount[$node] === 1 && $this->nodeWordBegin[$node] === $wordPosition) {
            return false;
        }

        $currentNodeWordLength = min($this->nodeWordEnd[$node] - $this->nodeWordBegin[$node], self::MAX_LENGTH - 1);

        // Do min edit distance
        $currentLength = $this->calculateMaxLength(
            $wordStart,
            $wordPosition,
            $node,
            $maxErrors,
            $currentNodeWordLength
        );

        if ($currentLength === 0) {
            return false;
        }

        if ($currentLength >= self::MAX_LENGTH - 1) {
            $this->reportBufferShortage($this->nodeWordBegin[$node], $currentNodeWordLength);
        }

        // calculate cheapest match
        if ($maxErrors === 0) {
            $best = 0;
            $iBest = $currentLength;
            $jBest = $currentLength;
        } else {
            $best = $maxErrors + 42;
            $iBest = 0;
            $jBest = 0;

            for ($k = 0; $k <= $currentLength; $k++) {
                $i = $currentLength - $k;
                $j = $currentLength;

                if ($this->edBuffer[$i][$j] < $best) {
                    $best = $this->edBuffer[$i][$j];
                    $iBest = $i;
                    $jBest = $j;
                }

                $i = $currentLength;
                $j = $currentLength - $k;

                if ($this->edBuffer[$i][$j] < $best) {
                    $best = $this->edBuffer[$i][$j];
                    $iBest = $i;
                    $jBest = $j;
                }
            }
        }

        $nodeBegin = $this->nodeWordBegin[$node];

        while (
            $wordPosition + $iBest < $this->wordCount
            && $jBest < $currentNodeWordLength
        ) {
            $iChar = $this->word[$wordPosition + $iBest];
            $jChar = $this->word[$nodeBegin + $jBest];

            if (
                $iChar === $jChar
                || $iChar->tokenCode === -1
                || $jChar->tokenCode === -1
                || $iChar->hash !== $jChar->hash
            ) {
                break;
            }

            $iBest++;
            $jBest++;
        }

        $numReported = 0;

        if ($currentLength === $currentNodeWordLength) {
            // we may proceed
            for ($e = $this->nodeChildFirst[$node]; $e >= 0; $e = $this->nodeChildNext[$e]) {
                if (
                    $this->matchWord(
                        $wordStart,
                        $wordPosition + $iBest,
                        $this->nodeChildNode[$e],
                        $nodeWordLength + $jBest,
                        $maxErrors - $best
                    )
                ) {
                    $numReported++;
                }
            }
        }

        // do not report locally if had reports in exactly one subtree (would be pure subclone)
        if ($numReported === 1) {
            return true;
        }

        // disallow tail changes
        while (
            $iBest > 0
            && $jBest > 0
            && $this->word[$wordPosition + $iBest - 1]->hash !== $this->word[$nodeBegin + $jBest - 1]->hash
        ) {
            if (
                $iBest > 1
                && $this->word[$wordPosition + $iBest - 2]->hash === $this->word[$nodeBegin + $jBest - 1]->hash
            ) {
                $iBest--;
            } elseif (
                $jBest > 1
                && $this->word[$wordPosition + $iBest - 1]->hash === $this->word[$nodeBegin + $jBest - 2]->hash
            ) {
                $jBest--;
            } else {
                $iBest--;
                $jBest--;
            }
        }

        // report if real clone
        if ($iBest > 0 && $jBest > 0) {
            $numReported++;
            $this->reportClone($wordStart, $wordPosition + $iBest, $node, $jBest, $nodeWordLength + $jBest);
        }

        return $numReported > 0;
    }

    /**
     * Calculates the maximum length we may take along the word to the current $node
     * (respecting the number of errors to make). *.
     *
     * @param int $wordStart the start position of the currently matched word (position in the input word)
     * @param int $wordPosition the current position along the input word
     * @param int $node the node we are currently at (i.e. the edge leading to this node is relevant to us).
     * @param int $maxErrors the number of errors still allowed
     * @param int $currentNodeWordLength the length of the word found along the nodes (this may be different from the
     *                                   actual length due to buffer limits)
     *
     * @return int the maximal length that can be taken
     */
    private function calculateMaxLength(
        int $wordStart,
        int $wordPosition,
        int $node,
        int $maxErrors,
        int $currentNodeWordLength
    ): int {
        $wordCount = $this->wordCount;
        $nodeWordBegin = $this->nodeWordBegin[$node];

        // FIX: Fast Path for exact matches (edit-distance = 0).
        // Bypasses the heavy O(N^2) Levenshtein distance matrix computation,
        // reducing execution time on dense operator blocks by over 90%.
        if ($maxErrors === 0) {
            for ($currentLength = 1; $currentLength <= $currentNodeWordLength; $currentLength++) {
                if ($wordPosition + $currentLength >= $wordCount) {
                    break;
                }

                $iIndex = $wordPosition + $currentLength - 1;

                $iChar = $this->word[$iIndex];
                $jChar = $this->word[$nodeWordBegin + $currentLength - 1];

                if ($iChar->tokenCode === -1 || $jChar->tokenCode === -1 || $iChar->hash !== $jChar->hash) {
                    break;
                }
            }

            return $currentLength - 1;
        }

        $this->edBuffer[0][0] = 0;
        $headRemaining = $this->headEquality - ($wordPosition - $wordStart);

        for ($currentLength = 1; $currentLength <= $currentNodeWordLength; $currentLength++) {
            if ($wordPosition + $currentLength >= $wordCount) {
                break;
            }

            // deal with case that character may not be matched (sentinel or barrier!)
            $iChar = $this->word[$wordPosition + $currentLength - 1];
            $jChar = $this->word[$nodeWordBegin + $currentLength - 1];

            if ($iChar->tokenCode === -1 || $jChar->tokenCode === -1) {
                break;
            }

            $iCharHash = $iChar->hash;
            $jCharHash = $jChar->hash;

            // Fast-forward during headEquality window where 0 errors are permitted
            if ($currentLength <= $headRemaining) {
                if ($iCharHash !== $jCharHash) {
                    break;
                }

                for ($k = 0; $k <= $currentLength; $k++) {
                    $diff = $currentLength - $k;
                    $this->edBuffer[$k][$currentLength] = $diff;
                    $this->edBuffer[$currentLength][$k] = $diff;
                }

                continue;
            }

            /** @var int<1, max> $best */
            $best = $currentLength;
            $this->edBuffer[0][$currentLength] = $currentLength;
            $this->edBuffer[$currentLength][0] = $currentLength;

            // Inlined matrix completion for column $currentLength (jCharHash is invariant)
            for ($k = 1; $k < $currentLength; $k++) {
                $val1 = $this->edBuffer[$k - 1][$currentLength];
                $val2 = $this->edBuffer[$k][$currentLength - 1];
                $insertDelete = 1 + ($val1 < $val2 ? $val1 : $val2);
                $change = $this->edBuffer[$k - 1][$currentLength - 1]
                    + ($this->word[$wordPosition + $k - 1]->hash === $jCharHash ? 0 : 1);

                $val = $insertDelete < $change ? $insertDelete : $change;
                $this->edBuffer[$k][$currentLength] = $val;
                if ($val < $best) {
                    $best = $val;
                }
            }

            // Inlined matrix completion for row $currentLength (iCharHash is invariant)
            $prevRow = $this->edBuffer[$currentLength - 1];
            $currRow = &$this->edBuffer[$currentLength];

            for ($k = 1; $k < $currentLength; $k++) {
                $val1 = $prevRow[$k];
                $val2 = $currRow[$k - 1];
                $insertDelete = 1 + ($val1 < $val2 ? $val1 : $val2);
                $change = $prevRow[$k - 1]
                    + ($iCharHash === $this->word[$nodeWordBegin + $k - 1]->hash ? 0 : 1);

                $val = $insertDelete < $change ? $insertDelete : $change;
                $currRow[$k] = $val;
                if ($val < $best) {
                    $best = $val;
                }
            }

            // Diagonal element ($currentLength, $currentLength)
            $val1 = $prevRow[$currentLength];
            $val2 = $currRow[$currentLength - 1];
            $insertDelete = 1 + ($val1 < $val2 ? $val1 : $val2);
            $change = $prevRow[$currentLength - 1] + ($iCharHash === $jCharHash ? 0 : 1);

            $val = $insertDelete < $change ? $insertDelete : $change;
            $currRow[$currentLength] = $val;
            unset($currRow);

            if ($val < $best) {
                $best = $val;
            }

            if ($best > $maxErrors) {
                break;
            }
        }
        $currentLength--;

        return $currentLength;
    }

    private function reportClone(
        int $wordBegin,
        int $wordEnd,
        int $currentNode,
        int $nodeWordPos,
        int $nodeWordLength
    ): void {
        $length = $wordEnd - $wordBegin;

        if ($length < $this->minLength || $nodeWordLength < $this->minLength) {
            return;
        }

        $otherClones = new PairList();
        $this->findRemainingClones(
            $otherClones,
            $nodeWordLength,
            $currentNode,
            $this->nodeWordEnd[$currentNode] - $this->nodeWordBegin[$currentNode] - $nodeWordPos,
            $wordBegin
        );

        $otherClonesSize = $otherClones->size();
        $occurrences = 1 + $otherClonesSize;

        // check whether we may start from here
        $t = $this->word[$wordBegin];
        $newInfo = new CloneInfo($length, $wordBegin, $occurrences, $t, $otherClones);

        for ($index = max(0, $wordBegin - self::INDEX_SPREAD + 1); $index <= $wordBegin; $index++) {
            $existingClones = $this->cloneInfos[$index] ?? null;

            if ($existingClones !== null) {
                $later = $wordBegin - $index;
                foreach ($existingClones as $cloneInfo) {
                    if ($cloneInfo->dominates($newInfo, $later)) {
                        // we already have a dominating clone, so ignore
                        return;
                    }
                }
            }
        }

        // add clone to $otherClones to avoid getting more duplicates
        for ($i = $wordBegin; $i < $wordEnd; $i += self::INDEX_SPREAD) {
            $this->cloneInfos[$i][]
                = new CloneInfo($length - ($i - $wordBegin), $wordBegin, $occurrences, $t, $otherClones);
        }

        for ($clone = 0; $clone < $otherClonesSize; $clone++) {
            $start = $otherClones->getFirst($clone);
            $otherLength = $otherClones->getSecond($clone);

            for ($i = 0; $i < $otherLength; $i += self::INDEX_SPREAD) {
                $this->cloneInfos[$start + $i][]
                    = new CloneInfo($otherLength - $i, $wordBegin, $occurrences, $t, $otherClones);
            }
        }
    }

    /**
     * Fills a list of pairs giving the start positions and lengths of the remaining clones.
     *
     * @param PairList $clonePositions the clone positions being filled (start position and length)
     * @param int $nodeWordLength the length of the word along the nodes
     * @param int $currentNode the node we are currently at
     * @param int $distance  the distance along the word leading to the current node
     * @param int $wordStart the start of the currently searched word
     */
    private function findRemainingClones(
        PairList $clonePositions,
        int $nodeWordLength,
        int $currentNode,
        int $distance,
        int $wordStart
    ): void {
        if ($this->nodeChildFirst[$currentNode] < 0) {
            $start = $this->wordCount - $distance - $nodeWordLength;

            if ($start !== $wordStart) {
                $clonePositions->add($start, $nodeWordLength);
            }

            return;
        }

        for (
            $nextNode = $this->nodeChildFirst[$currentNode];
            $nextNode >= 0;
            $nextNode = $this->nodeChildNext[$nextNode]
        ) {
            $node = $this->nodeChildNode[$nextNode];
            $childDistance = $distance + $this->nodeWordEnd[$node] - $this->nodeWordBegin[$node];

            if ($this->nodeChildFirst[$node] < 0) {
                $start = $this->wordCount - $childDistance - $nodeWordLength;

                if ($start !== $wordStart) {
                    $clonePositions->add($start, $nodeWordLength);
                }
            } else {
                $this->findRemainingClones(
                    $clonePositions,
                    $nodeWordLength,
                    $node,
                    $childDistance,
                    $wordStart
                );
            }
        }
    }
}
