<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy\SuffixTree;

/**
 * Efficient linear time constructible suffix tree using Ukkonen's online construction algorithm
 * (E. Ukkonen: "On-line construction of suffix trees").
 * Most of the comments reference this paper and it might be hard to follow without knowing at least the basics of it.
 * <p>
 * We use some conventions which are slightly different from the paper however:
 * <ul>
 * <li>The names of the variables are different, but we give a translation into Ukkonen's names.</li>
 * <li>Many variables are made "global" by realizing them as fields. This way we can easily deal with those tuple
 * return values without constructing extra classes.</li>
 * <li>String indices start at 0 (not at 1).</li>
 * <li>Substrings are marked by the first index and the index after the last one (just as in C++ STL) instead of the
 * first and the last index (i.e. intervals are right-open instead of closed). This makes it more intuitive to express
 * the empty string (i.e. (i,i) instead of (i,i-1)).</li>
 * </ul>
 * <p>
 * Everything but the construction itself is protected to simplify increasing its functionality by subclassing but
 * without introducing new method calls.
 */
class SuffixTree
{
    /**
     * Infinity in this context.
     */
    protected int $infty;

    /**
     * The word we are working on.
     *
     * @var AbstractToken[]
     */
    protected array $word;

    /**
     * The number of nodes created so far.
     */
    protected int $numNodes = 0;

    /**
     * For each node this holds the index of the first character of {@link #word} labeling the transition <b>to</b>
     * this node. This corresponds to the <em>k</em> for a transition used in Ukkonen's paper.
     *
     * @var int[]
     */
    protected array $nodeWordBegin;

    /**
     * For each node this holds the index of the one after the last character of {@link #word} labeling the transition
     * <b>to</b> this node. This corresponds to the <em>p</em> for a transition used in Ukkonen's paper.
     *
     * @var int[]
     */
    protected array $nodeWordEnd;

    /** For each node its suffix link (called function <em>f</em> by Ukkonen).
     *
     * @var int[]
     */
    protected array $suffixLink;

    /**
     * The next node function realized as a hash table. This corresponds to the <em>g</em> function used in
     * Ukkonen's paper.
     */
    protected SuffixTreeHashTable $nextNode;

    /**
     * An array giving for each node the index where the first child will be stored (or -1 if it has no children).
     * It is initially empty and will be filled "on demand" using
     * {@link org.conqat.engine.code_clones.detection.suffixtree.SuffixTreeHashTable#extractChildLists
     * (int[], int[], int[])}.
     *
     * @var int[]
     */
    protected array $nodeChildFirst = [];

    /**
     * This array gives the next index of the child list or -1 if this is the last one. It is initially empty and
     * will be filled "on demand" using
     * {@link org.conqat.engine.code_clones.detection.suffixtree.SuffixTreeHashTable#extractChildLists
     * (int[], int[], int[])}.
     *
     * @var int[]
     */
    protected array $nodeChildNext = [];

    /**
     * This array stores the actual name (=number) of the mode in the child list. It is initially empty and will be
     * filled "on demand" using
     * {@link org.conqat.engine.code_clones.detection.suffixtree.SuffixTreeHashTable#extractChildLists
     * (int[], int[], int[])}.
     *
     * @var int[]
     */
    protected array $nodeChildNode = [];

    /**
     * The node we are currently at as a "global" variable (as it is always passed unchanged).
     * This is called <i>s</i> in Ukkonen's paper.
     */
    private int $currentNode = 0;

    /**
     * Beginning of the word part of the reference pair. This is kept "global" (in constrast to the end) as this is
     * passed unchanged to all functions. Ukkonen calls this <em>k</em>.
     */
    private int $refWordBegin = 0;

    /**
     * This is the new (or old) explicit state as returned by {@link #testAndSplit(int, Object)}.
     * Ukkonen calls this <em>r</em>.
     */
    private int $explicitNode = 0;

    /**
     * Create a new suffix tree from a given word. The word given as parameter is used internally and should not be
     * modified anymore, so copy it before if required.
     *
     * @param AbstractToken[] $word
     */
    public function __construct(array $word)
    {
        $this->word = $word;
        $size = count($word);
        $this->infty = $size;

        $expectedNodes = 2 * $size;
        $data = array_fill(0, $expectedNodes, 0);
        $this->nodeWordBegin = $data;
        $this->nodeWordEnd = $data;
        $this->suffixLink = $data;
        $this->nextNode = new SuffixTreeHashTable($expectedNodes);

        $this->createRootNode();

        for ($i = 0; $i < $size; $i++) {
            $this->update($i);
            $this->canonize($i + 1);
        }
    }

    /**
     * This method makes sure the child lists are filled (required for traversing the tree).
     */
    protected function ensureChildLists(): void
    {
        if (count($this->nodeChildFirst) < $this->numNodes) {
            $data = array_fill(0, $this->numNodes, 0);
            $this->nodeChildFirst = $data;
            $this->nodeChildNext = $data;
            $this->nodeChildNode = $data;
            $this->nextNode->extractChildLists($this->nodeChildFirst, $this->nodeChildNext, $this->nodeChildNode);
        }
    }

    /**
     * Creates the root node.
     */
    private function createRootNode(): void
    {
        $this->numNodes = 1;
        $this->nodeWordBegin[0] = 0;
        $this->nodeWordEnd[0] = 0;
        $this->suffixLink[0] = -1;
    }

    /**
     * The <em>update</em> function as defined in Ukkonen's paper. This inserts the character at charPos into the tree.
     * It works on the canonical reference pair ({@link #currentNode}, ({@link #refWordBegin}, charPos)).
     */
    private function update(int $charPos): void
    {
        $lastNode = 0;

        while (!$this->testAndSplit($charPos, $this->word[$charPos])) {
            $newNode = $this->numNodes++;
            $this->nodeWordBegin[$newNode] = $charPos;
            $this->nodeWordEnd[$newNode] = $this->infty;
            $this->nextNode->put($this->explicitNode, $this->word[$charPos], $newNode);

            if ($lastNode !== 0) {
                $this->suffixLink[$lastNode] = $this->explicitNode;
            }
            $lastNode = $this->explicitNode;
            $this->currentNode = $this->suffixLink[$this->currentNode];
            $this->canonize($charPos);
        }

        if ($lastNode !== 0) {
            $this->suffixLink[$lastNode] = $this->currentNode;
        }
    }

    /**
     * The <em>test-and-split</em> function as defined in Ukkonen's paper. This checks whether the state given by the
     * canonical reference pair ({@link #currentNode}, ({@link #refWordBegin}, refWordEnd)) is the end
     * point (by checking whether a transition for the <code>nextCharacter</code> exists).
     * Additionally the state is made explicit if it not already is and this is not the end-point.
     * It returns true if the end-point was reached. The newly created (or reached)
     * explicit node is returned in the "global" variable.
     */
    private function testAndSplit(int $refWordEnd, AbstractToken $nextCharacter): bool
    {
        if ($this->currentNode < 0) {
            // trap state is always end state
            return true;
        }

        if ($refWordEnd <= $this->refWordBegin) {
            if ($this->nextNode->get($this->currentNode, $nextCharacter) < 0) {
                $this->explicitNode = $this->currentNode;

                return false;
            }

            return true;
        }

        $next = $this->nextNode->get($this->currentNode, $this->word[$this->refWordBegin]);

        if ($nextCharacter->equals($this->word[$this->nodeWordBegin[$next] + $refWordEnd - $this->refWordBegin])) {
            return true;
        }

        // not an end-point and not explicit, so make it explicit.
        $this->explicitNode = $this->numNodes++;
        $this->nodeWordBegin[$this->explicitNode] = $this->nodeWordBegin[$next];
        $this->nodeWordEnd[$this->explicitNode] = $this->nodeWordBegin[$next] + $refWordEnd - $this->refWordBegin;
        $this->nextNode->put($this->currentNode, $this->word[$this->refWordBegin], $this->explicitNode);

        $this->nodeWordBegin[$next] += $refWordEnd - $this->refWordBegin;
        $this->nextNode->put($this->explicitNode, $this->word[$this->nodeWordBegin[$next]], $next);

        return false;
    }

    /**
     * The <em>canonize</em> function as defined in Ukkonen's paper.
     * Changes the reference pair (currentNode, (refWordBegin, refWordEnd)) into a canonical reference pair.
     * It works on the "global" variables {@link #currentNode} and {@link #refWordBegin} and the parameter,
     * writing the result back to the globals.
     *
     * @param int $refWordEnd one after the end index for the word of the reference pair
     */
    private function canonize(int $refWordEnd): void
    {
        if ($this->currentNode === -1) {
            // explicitly handle trap state
            $this->currentNode = 0;
            $this->refWordBegin++;
        }

        if ($refWordEnd <= $this->refWordBegin) {
            // empty word, so already canonical
            return;
        }

        $next = $this->nextNode->get(
            $this->currentNode,
            $this->word[$this->refWordBegin]
        );

        while ($refWordEnd - $this->refWordBegin >= $this->nodeWordEnd[$next] - $this->nodeWordBegin[$next]) {
            $this->refWordBegin += $this->nodeWordEnd[$next] - $this->nodeWordBegin[$next];
            $this->currentNode = $next;

            if ($refWordEnd > $this->refWordBegin) {
                $next = $this->nextNode->get($this->currentNode, $this->word[$this->refWordBegin]);
            } else {
                break;
            }
        }
    }
}
