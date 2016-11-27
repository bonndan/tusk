<?php
namespace Tusk\NodeVisitor;

/**
 * Turns __call into methodMissing.
 *
 */
class MagicCall extends \PhpParser\NodeVisitorAbstract implements InfluencingVisitor
{
    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Stmt\ClassMethod)
            return;
     
        if ($node->name == '__call') {
            $node->name = "methodMissing";
            $node->params = [];
            $node->params[0] = new \PhpParser\Node\Param('name');
            $node->params[0]->type = 'String';
            $node->params[1] = new \PhpParser\Node\Param('arguments');
            $node->params[1]->type = 'def';
        }
    }

}
