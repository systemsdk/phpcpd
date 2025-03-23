<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

/** Stores information on a clone. */
class CloneInfo
{
    /**
     * Length of the clone in tokens.
     */
    public int $length;

    /**
     * Position in word list.
     */
    public int $position;

    public AbstractToken $token;

    /**
     * Related clones.
     */
    public PairList $otherClones;

    /**
     * Number of occurrences of the clone.
     */
    private int $occurrences;

    public function __construct(
        int $length,
        int $position,
        int $occurrences,
        AbstractToken $token,
        PairList $otherClones
    ) {
        $this->length = $length;
        $this->position = $position;
        $this->occurrences = $occurrences;
        $this->token = $token;
        $this->otherClones = $otherClones;
    }

    /**
     * Returns whether this clone info dominates the given one, i.e. whether both {@link #length} and
     * {@link #occurrences} s not smaller.
     *
     * @param int $later the amount the given clone starts later than the "this" clone
     */
    public function dominates(self $ci, int $later): bool
    {
        return $this->length - $later >= $ci->length && $this->occurrences >= $ci->occurrences;
    }
}
