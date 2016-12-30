<?php
namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;

/**
 * Replaces implode with join.
 * 
 */
class Implode_ implements NodeExchanger
{
    public function handleNode(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name == 'implode') 
            return $this->replaceImplode($node);
    }

    private function replaceImplode(Node\Expr\FuncCall $node)
    {
        $glue = $node->args[0];
        $arr = $node->args[1]->value;
        
        return new Node\Expr\MethodCall(clone $arr, 'join', [$glue]);
    }

}
