<?php

namespace Tusk\NodeVisitor;

/**
 * Finds variable definitions in functionlike nodes and marks them.
 * 
 * 
 */
class VariableDefinition extends \PhpParser\NodeVisitorAbstract
{
    const MARKER = 'isDefinition';
    
    private $vars;

    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\FunctionLike)
            return;

        $this->vars = [];
        $this->checkParams($node);
        $this->checkDefs($node);
    }
    
    private function checkParams(\PhpParser\Node\FunctionLike $node)
    {
        foreach ($node->getParams() as $param) {
            $this->vars[$param->name] = $param;
        }
    }

    private function checkDefs(\PhpParser\NodeAbstract $node)
    {
        if ($node instanceof \PhpParser\Node\Expr\Assign) {
            if (!$node->var instanceof \PhpParser\Node\Expr\Variable)
                return;
            
            $name = $node->var->name;
            if (!isset($this->vars[$name])) {
                $node->var->setAttribute(self::MARKER, true);
                $this->vars[$name] = $node->var;
            }

            return;
        }

        if (isset($node->stmts))
            foreach ($node->stmts as $subnode) {
                $this->checkDefs($subnode);
            }
    }

}
