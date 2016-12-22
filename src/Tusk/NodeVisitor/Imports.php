<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Use_;
use Tusk\Configuration;
use Tusk\State;

/**
 * Description of Imports
 *
 */
class Imports extends StatefulVisitor 
{
    /**
     * @var Configuration
     */
    private $config;

    public function __construct(State $state, Configuration $config)
    {
        parent::__construct($state);
        $this->config = $config;
        
        foreach ($this->config->alwaysImport as $import) {
            $this->state->addImport($import);
        }
    }
    
    public function enterNode(Node $node)
    {
        if ($node instanceof StaticPropertyFetch
            || $node instanceof StaticCall
            || $node instanceof ClassConstFetch
        ) {
            if ($node->class instanceof Node\Expr\Variable)
                return;
            $this->handleName($node->class->getFirst());
        }
        
        if ($node instanceof Param && $node->type instanceof Name) {
            $name = $node->type->getFirst();
            if (!$this->state->isUsed($name))
                $this->handleName($name);
        }
        
        /*
         * use statements aliases need to be registered. otherwise unwanted
         * imports might appear
         */
        if ($node instanceof Node\Stmt\UseUse) {
            $name = $node->alias;
            if (!$this->state->isUsed($name))
                $this->state->addUse ($name);
        }
    }
    
    private function handleName(string $name) 
    {
        $onDemand = $this->config->onDemandImport;
        if (array_key_exists($name, $onDemand)) {
            $this->state->addImport($onDemand[$name]);
            $this->state->addUse($name);
        }
    }
}
