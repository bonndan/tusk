<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\NodeVisitorAbstract;
use Tusk\NodeVisitor\InfluencingVisitor;

/**
 * Tries to rewrite reflection stuff.
 * 
 * 
 * 
 */
class Reflection extends NodeVisitorAbstract implements InfluencingVisitor
{

    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Expr\New_) {
            return;
        }

        if ($node->class == 'ReflectionClass') {
            return $this->handleReflectionClass($node);
        }
    }

    private function handleReflectionClass(\PhpParser\Node $node)
    {
        $val = $node->args[0]->value;
        $name = ($val instanceof \PhpParser\Node\Expr\Variable) ? $val->name : $val->value;
        return new \PhpParser\Node\Expr\StaticPropertyFetch(
            new \PhpParser\Node\Name((string) $name), 
            'metaClass'
        );
    }

}
