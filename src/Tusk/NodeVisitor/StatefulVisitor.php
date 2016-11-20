<?php

namespace Tusk\NodeVisitor;

/**
 * A visitor having a state.
 * 
 * 
 */
abstract class StatefulVisitor extends \PhpParser\NodeVisitorAbstract
{
    /**
     * @var \Tusk\State
     */
    protected $state;
    
    public function __construct(\Tusk\State $state)
    {
        $this->state = $state;
    }
}