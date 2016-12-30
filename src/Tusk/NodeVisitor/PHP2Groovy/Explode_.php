<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;

/**
 * replaces explode with split
 */
class Explode_
{
    public function handleNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name == 'explode') 
            return $this->replace($node);
    }

    private function replace(Node\Expr\FuncCall $node)
    {
        $sep = $node->args[0];
        $str = $node->args[1]->value;
        
        return new Node\Expr\MethodCall(clone $str, 'split', [$sep]);
    }
    
}
