<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitorAbstract;

/**
 * Counts the depths of loop nesting.
 * 
 * 
 */
class NestedLoop extends NodeVisitorAbstract
{
    const LABEL_PREFIX = 'loop';
    const DEPTH = 'depth';
    const OUTER_LOOP = 'outerLoop';
    const TARGET_LOOP = 'targetLoop';
    
    /**
     * flag to show the loop needs a label
     */
    const LEVEL_BREAKS = "hasLevelBreaks";

    private $level = 0;
    
    /**
     * @var Node|null
     */
    private $outerLoop;

    public function enterNode(Node $node)
    {
        if ($this->isLoop($node)) {
            $this->level++;
            $node->setAttribute(self::DEPTH, $this->level);
            $node->setAttribute(self::OUTER_LOOP, $this->outerLoop);
            $this->outerLoop = $node;
            
            $bcs = $this->getBreakContinueLevels($node);
            if (empty($bcs))
                return;
            
            $node->setAttribute(self::LEVEL_BREAKS, true);
            foreach ($bcs as $bc) {
                /* @var $bc Node\Stmt\Break_|Node\Stmt\Continue_ */
                if ($bc->num instanceof Node\Scalar\LNumber)
                    $targetLoop = self::LABEL_PREFIX . ($this->level - ($bc->num->value-1));
                else
                    $targetLoop = $bc->num;
                $bc->setAttribute(self::TARGET_LOOP, $targetLoop);
            }
        }
    }
    
    public function leaveNode(Node $node)
    {
        if ($this->isLoop($node)) {
            $this->level--;
            $outer = $node->getAttribute(self::OUTER_LOOP);
            if ($node->getAttribute(self::LEVEL_BREAKS) && $outer)
                $outer->setAttribute(self::LEVEL_BREAKS, true);
            
            $this->outerLoop = $node->getAttribute(self::OUTER_LOOP);
        }
    }

    private function isLoop(Node $node) : bool
    {
        return $node instanceof Foreach_
            || $node instanceof For_
            || $node instanceof While_
            || $node instanceof Do_;
    }

    /**
     * Returns all break/continue statements which affect nested loop levels.
     * 
     * @param Node $node
     * @return Node[]
     */
    private function getBreakContinueLevels(Node $node)
    {
        $nodes=[];
        foreach ($node->stmts as $stmt) {
            /* @var $stmt Node */
            if ($this->isLoop($stmt))
                continue;
            if (($stmt instanceof Node\Stmt\Break_ || $stmt instanceof Node\Stmt\Continue_)
                && $stmt->num) {
                $nodes[] = $stmt;
            }
        }
        
        return $nodes;
    }
}
