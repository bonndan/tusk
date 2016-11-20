<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

/**
 * Ensures that all trait methods are public.
 * 
 * 
 */
class PublicTraitMethods extends NodeVisitorAbstract
{

    public function enterNode(Node $node)
    {
        if (!$node instanceof Trait_) {
            return;
        }

        $this->examineStmts($node->stmts);
    }

    private function examineStmts(array $stmts)
    {
        foreach ($stmts as $stmt) {
            /* @var $stmt Node */
            if (!$stmt instanceof ClassMethod)
                continue;

            if ($stmt->isPrivate()) {
                $stmt->type &= ~Class_::MODIFIER_PRIVATE;
            }

            if ($stmt->isProtected()) {
                $stmt->type &= ~Class_::MODIFIER_PROTECTED;
            }

            if (!$stmt->type) {
                $stmt->type = Class_::MODIFIER_PUBLIC;
            } else {
                $stmt->type = $stmt->type | Class_::MODIFIER_PUBLIC;
            }
        }
    }

}
