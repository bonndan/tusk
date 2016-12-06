<?php
namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;

/**
 * Grabs the namespace and sets it to the state.
 * 
 * If the state already has a package but the class has no namespace, the package
 * is used instead.
 * 
 */
class Namespace_ extends StatefulVisitor
{
    private $foundNamespace = false;
    
    public function enterNode(Node $node)
    {
        if (!$node instanceof NamespaceNode)
            return;
     
        $this->state->setNamespace($node->name);
        $this->foundNamespace = $node->name;
    }

    public function afterTraverse(array $nodes)
    {
        if (!$this->foundNamespace && $this->state->getPackage()) {
            array_unshift($nodes, new NamespaceNode(new Name($this->state->getPackage())));
            return $nodes;
        }
    }
}
