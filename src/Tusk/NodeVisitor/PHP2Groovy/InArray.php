<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;


/**
 * Converts in_array to contains
 *
 */
class InArray implements NodeExchanger
{
    public function handleNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name == 'in_array') 
            return $this->replace($node);
    }

    private function replace(Node\Expr\FuncCall $node)
    {
        $needle = $node->args[0];
        $haystack = $node->args[1]->value;
        
        $call = new Node\Expr\MethodCall(clone $haystack, 'contains', [$needle]);
        $call->setDocComment(new \PhpParser\Comment\Doc('TODO check, was in_array'));
        return $call;
    }
}
