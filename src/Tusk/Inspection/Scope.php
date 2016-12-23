<?php

namespace Tusk\Inspection;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\ClassLike;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeAbstract;

/**
 * Scope interface.
 * 
 * 
 * 
 */
class Scope
{

    const SCOPE = 'scope';
    const VAR_DEFINITION = 'varDefinition';
    const CONDITION = 'isCondition';

    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var Scope
     */
    private $parent;

    /**
     * Node where the scope begins
     * @var Node
     */
    private $scopeRoot = null;

    /**
     * scope variables
     * @var Node[]
     */
    private $vars = [];

    /**
     * Variables introduced in child scope (accessible in top scope in php, but
     * not in groovy).
     * 
     * @var string[]
     */
    private $introducedByChildScopes = [];

    /**
     * @var bool
     */
    private $debug = false;
    
    /**
     * Returns the scope of the node.
     * 
     * @param Node $node
     * @return Scope
     */
    public static function of(Node $node)
    {
        return $node->getAttribute(self::SCOPE);
    }

    /**
     * Checks if the node has its own subscope.
     * 
     * @param Node $node
     * @return bool
     */
    public static function hasOwnScope(Node $node): bool
    {
        return $node instanceof ClassLike ||
            $node instanceof FunctionLike ||
            $node instanceof While_ ||
            $node instanceof For_ ||
            $node instanceof Foreach_ ||
            $node instanceof Do_ ||
            $node instanceof If_ ||
            $node instanceof ElseIf_ ||
            $node instanceof Else_ ||
            $node instanceof Switch_ ||
            $node instanceof TryCatch;
    }

    public function __construct(Node $root = null, Scope $parent = null)
    {
        $this->nameResolver = new NameResolver();
        $this->parent = $parent;
        if (!$root)
            return;

        $this->scopeRoot = $root;
        $root->setAttribute(self::SCOPE, $this);
    }

    private function getName(NodeAbstract $node): string
    {
        $name = $this->nameResolver->getFQN($node);

        if ($name == 'this') {
            return '';
        }

        return $name;
    }

    public function addVar(NodeAbstract $node)
    {
        $node->setAttribute(Scope::SCOPE, $this);
        $name = $this->getName($node);
        if (strpos($name, 'this.') !== FALSE) {
            return;
        }
        if (empty($name))
            return;
        
        //"def" not used if variable already known or inside conditions
        $parent = \Tusk\NodeVisitor\TreeRelation::parentOf($node);
        $isCondition = ($parent && $parent->getAttribute(Scope::CONDITION));
        
        if (!$this->hasVar($node) && !$isCondition) {
            $node->setAttribute(self::VAR_DEFINITION, true);
            if (isset($this->introducedByChildScopes[$name])) {
                $this->introducedByChildScopes[$name] = Scope::of($node);
                if ($this->debug)
                    var_dump('manually introduced def ' . $name . ' into ' . $this);
            }
        }
        
        if ($this->debug)
            var_dump('Added '  . $name . ' to scope '. $this);
        $this->vars[$name] = $node;

        $parentNode = \Tusk\NodeVisitor\TreeRelation::parentOf($node);
        if (!$parentNode instanceof Node\Expr\Assign && !$this->hasVar($node)) {
            if ($this->debug)
                var_dump(get_class($parentNode) . $name . ' not assigned to ' . $this);
            $this->introducedByChildScopes[$name] = $this;
        }
        
        if ($this->parent && !$this->parent->hasVar($node)) {
            $this->parent->introduceVar($node);
        }

    }

    /**
     * Introduce a variable to the scope which is defined in a subscope.
     * 
     * Does NOT add the variable.
     * 
     * @param mixed $node
     * @return void
     */
    public function introduceVar(NodeAbstract $node)
    {
        $name = $this->getName($node);
        if ($this->debug)
            var_dump(Scope::of($node)->__toString() . ' introducing ' . $name . ' to scope ' . $this);
        
        if (!array_key_exists($name, $this->introducedByChildScopes)) {
           
            $this->introducedByChildScopes[$name] = Scope::of($node);
        }

    }

    /**
     * Checks if a variable is known in the current or parent scopes.
     * 
     * @param string|\PhpParser\NodeAbstract $node
     * @return bool
     */
    public function hasVar($node): bool
    {
        if (is_string($node)) {
            if (array_key_exists($node, $this->vars))
                return true;
            return $this->parent && $this->parent->hasVar($node);
        }

        $name = $this->nameResolver->getFQN($node);
        $exists = isset($this->vars[$name]);
        if (!$exists || $this->nameResolver->isProperty($name)) {
            $rawName = $this->nameResolver->getLocalName($node);
            $exists = isset($this->vars[$rawName]);
        }

        if ($exists)
            return true;

        return $this->parent && $this->parent->hasVar($node);
    }

    /**
     * Returns the parent scope.
     * 
     * @return Scope|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function __toString()
    {
        if (!isset($this->scopeRoot) )
            return "root scope";
        
        return  $this->scopeRoot->getType() . ' on line ' . $this->scopeRoot->getLine();
    }

    /**
     * Returns all variables introduced in child scopes.
     * 
     * @return Scope[]
     */
    public function getVarsIntroducedByChildScopes(): array
    {
        return $this->introducedByChildScopes;
    }

}
