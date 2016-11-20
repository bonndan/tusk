<?php
namespace Tusk\NodeVisitor;

/**
 * Grabs the namespace and sets it to the state.
 * 
 *
 */
class Namespace_ extends StatefulVisitor
{
    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Namespace_)
            return;
     
        $this->state->setNamespace($node->name);
    }

}
