<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\NodeVisitorAbstract;
use Tusk\NodeVisitor\InfluencingVisitor;

/**
 * Ensures that the last case in a switch statement contains a break.
 * 
 * 
 * 
 */
class SwitchLastCaseBreak extends NodeVisitorAbstract implements InfluencingVisitor
{
    public function enterNode(Node $node)
    {
        if (!$node instanceof Switch_)
            return;
        
        $last = end($node->cases); /* @var $last Case_ */
        $lastStatement = end($last->stmts);
        if (!$lastStatement instanceof Return_ && !$lastStatement instanceof Break_) {
            $last->stmts[] = new Break_();
            return $node;
        }
    }
}
