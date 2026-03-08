<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

use function array_keys;

/**
 * Modernized PHP-native hash table for the SuffixTree.
 *
 * In the original Java implementation, a custom open-addressing hash table
 * using primitive arrays was required to avoid garbage collection overhead[cite: 438, 439].
 * In PHP, native arrays are already highly optimized hash tables written in C.
 * This class leverages PHP's internal arrays to store tree transitions,
 * completely eliminating the risk of crc32 collisions and improving performance.
 */
class SuffixTreeHashTable
{
    /**
     * The transition table.
     * Structure: $table[source_node_id][token_hash_key] = target_node_id
     *
     * @var array<int, array<string, int>>
     */
    private array $table = [];

    /**
     * Creates a new hash table.
     *
     * Note: The original Java port required the number of nodes to pre-allocate
     * memory and calculate prime numbers for the hash mask[cite: 440, 441, 453].
     * PHP handles array allocation and resizing dynamically at the engine level,
     * so this parameter is not needed.
     */
    public function __construct()
    {
    }

    /**
     * Returns the next node for the given (node, token) key pair.
     *
     * @param int $keyNode The source node ID.
     * @param AbstractToken $keyChar The token representing the transition edge.
     *
     * @return int The ID of the target node, or -1 if no transition exists[cite: 457].
     */
    public function get(int $keyNode, AbstractToken $keyChar): int
    {
        $hash = $this->generateKey($keyChar);

        return $this->table[$keyNode][$hash] ?? -1;
    }

    /**
     * Inserts a transition into the hash table.
     *
     * Registers that moving from $keyNode via $keyChar leads to $resultNode[cite: 461].
     *
     * @param int $keyNode The source node ID.
     * @param AbstractToken $keyChar The token representing the transition edge.
     * @param int $resultNode The target node ID.
     */
    public function put(int $keyNode, AbstractToken $keyChar, int $resultNode): void
    {
        $hash = $this->generateKey($keyChar);
        $this->table[$keyNode][$hash] = $resultNode;
    }

    /**
     * Extracts the list of child nodes for each node from the hash table
     * into three flat arrays representing a linked list.
     *
     * The Suffix Tree algorithm uses these flat arrays for extremely fast
     * linear traversals during the clone reporting phase[cite: 466].
     *
     * @param int[] $nodeFirstIndex An array giving for each node the index where the first child will be stored
     *                              (or -1 if none)[cite: 468].
     * @param int[] $nodeNextIndex An array giving the next index of the child list, or -1 if this is the last
     *                              one[cite: 468].
     * @param int[] $nodeChild An array storing the actual name (=number) of the node in the child list[cite: 468].
     */
    public function extractChildLists(array &$nodeFirstIndex, array &$nodeNextIndex, array &$nodeChild): void
    {
        foreach (array_keys($nodeFirstIndex) as $k) {
            $nodeFirstIndex[$k] = -1;
        }

        $free = 0;
        foreach ($this->table as $keyNode => $transitions) {
            foreach ($transitions as $resultNode) {
                $nodeChild[$free] = $resultNode;
                $nodeNextIndex[$free] = $nodeFirstIndex[$keyNode];
                $nodeFirstIndex[$keyNode] = $free++;
            }
        }
    }

    /**
     * Generates a collision-free string key for the token.
     *
     * The original algorithm relied on crc32 hashes which could collide
     * for different code snippets[cite: 479]. This method creates a precise
     * string key combining the token's type and its exact content,
     * ensuring perfect uniqueness in PHP's associative arrays.
     *
     * @param AbstractToken $token The token to hash.
     *
     * @return string A unique string key (e.g., "312_public").
     */
    private function generateKey(AbstractToken $token): string
    {
        return $token->tokenCode . '_' . $token->content;
    }
}
