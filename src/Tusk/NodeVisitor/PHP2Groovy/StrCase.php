<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;

/**
 * replaces string case function calls.
 * 
 * 
 * 
 */
class StrCase implements NodeExchanger
{
    private $funcs = [
        'strtolower' => 'toLowerCase',
        'strtoupper' => 'toUpperCase',
    ];
    
    public function handleNode(Node $node)
    {
        if ($node instanceof FuncCall && in_array($node->name, array_keys($this->funcs))) 
            return $this->replace($node);
    }

    private function replace(FuncCall $node)
    {
        $string = $node->args[0]->value;
        $name = (string)$node->name;
        return new Node\Expr\MethodCall($string, $this->funcs[$name]);
    }

}
