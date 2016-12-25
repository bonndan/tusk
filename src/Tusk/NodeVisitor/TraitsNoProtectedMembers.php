<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

/**
 * Ensures that all trait methods are public.
 * 
 * 
 */
class TraitsNoProtectedMembers extends NodeVisitorAbstract implements InfluencingVisitor
{

    public function enterNode(Node $node)
    {
        if (!$node instanceof Trait_) {
            return;
        }

        $this->examineStmts($node);
        //return $node;
    }

    private function examineStmts(Trait_ $node)
    {
        foreach ($node->stmts as $key => $stmt) {
            /* @var $stmt Node */
            if (!$stmt instanceof ClassMethod && !$stmt instanceof Property)
                continue;
            
            if ($stmt->isProtected()) {
                $stmt->flags &= ~Class_::MODIFIER_PROTECTED;
            }

            $node->stmts[$key] = $stmt;
        }
    }

}
