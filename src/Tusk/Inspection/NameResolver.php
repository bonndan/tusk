<?php
namespace Tusk\Inspection;

/**
 * Helper to deal with variable names.
 *
 * 
 */
class NameResolver
{
    public function getLocalName(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            return $node->props[0]->name;
        }

        if (isset($node->name) && $node->name == 'this') {
            return '';
        }

        return $this->getNodeName($node->name);
    }

    public function getFQN(\PhpParser\Node $node): string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            return $node->props[0]->name;
        }

        $name = $this->getLocalName($node);
        if (isset($node->var)) {
            $name = $this->getNodeName($node->var->name) . '.' . $name;
        }

        return $name;
    }

    public function getNodeName($node)
    {
        if (is_string($node))
            return $node;
        
        if ($node instanceof \PhpParser\Node\Expr\Variable)
            return $node->name;
        if ($node instanceof \PhpParser\Node\Scalar\String_)
            return $node->value;
    }

    public function isProperty(string $name)
    {
        return strpos($name, 'this') !== false;
    }

}
