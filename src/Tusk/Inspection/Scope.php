<?php
namespace Tusk\Inspection;

/**
 * A scope.
 * 
 * 
 * 
 */
class Scope
{
    const SCOPE = 'scope';
    
    /**
     * @var \PhpParser\Node|\PhpParser\Node\FunctionLike
     */
    private $root;
    
    /**
     * scope variables
     * @var \PhpParser\Node[]
     */
    private $vars = [];
    
    public function __construct(\PhpParser\Node $scopeRoot = null)
    {
        $this->root = $scopeRoot;
    }
    
    /**
     * Add a variable to the scope (node name is taken as index).
     * 
     * @param \PhpParser\NodeAbstract $node
     */
    public function addVar(\PhpParser\NodeAbstract $node)
    {
        $this->registerOn($node);
        $name = $this->getFQN($node);
        if ($name == 'this' || $name == '')
            return;
        $this->vars[$name] = $node;
    }
    
    /**
     * Registers the scope to the node attribute "scope".
     * 
     * @param \PhpParser\NodeAbstract $node
     */
    public function registerOn(\PhpParser\NodeAbstract $node)
    {
        $node->setAttribute(self::SCOPE, $this);
    }

    /**
     * Checks if a variable is present in the scope.
     * 
     * If a string is given, is is checked without modifications. If a node is
     * given it can be a property and then both local and global names are checked.
     * 
     * @param Node|string $node
     * @return bool
     */
    public function hasVar($node) : bool
    {
        if (is_string($node)) {
            return array_key_exists($node, $this->vars);
        }
        
        $name = $this->getFQN($node);
        if (empty($name)) {
            
            return false;
        }
        
        $exists = isset($this->vars[$name]);
               
        if (!$exists || $this->isProperty($name)) {
            $rawName = $this->getLocalName($node);
            $exists = isset($this->vars[$rawName]);
        }
        
        return $exists;
    }
    
    private function getLocalName(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            return $node->props[0]->name;
        }
        
        if (isset($node->name) && $node->name == 'this') {
            return '';
        }
        
        return is_string($node->name) ? $node->name : (string)$node->name->name;
    }
    
    private function getFQN(\PhpParser\Node $node) : string
    {
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            return $node->props[0]->name;
        }
        
        $name = $this->getLocalName($node);
        if (isset($node->var)) {
            $name = $node->var->name . '.' . $name;
        }
        
        return $name;
    }

    private function isProperty(string $name)
    {
        return strpos($name, 'this') !== false;
    }

}
