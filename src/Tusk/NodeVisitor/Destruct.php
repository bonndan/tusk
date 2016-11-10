<?php
namespace Tusk\NodeVisitor;

/**
 * Turns __destruct into close.
 *
 */
class Destruct extends \PhpParser\NodeVisitorAbstract
{
    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Stmt\ClassMethod)
            return;
        
        if ($node->name == '__destruct')
            $node->name = 'close';
    }

}
