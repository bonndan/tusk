<?php

namespace Tusk\NodeVisitor;

/**
 * Finds variable definitions in scopes nodes and marks them.
 * 
 * 
 */
class VariableDefinition extends \PhpParser\NodeVisitorAbstract
{

    const MARKER = 'isDefinition';

    /**
     * @var \Tusk\Inspection\Scope
     */
    private $scopes;
    
    public function __construct()
    {
        $this->scopes[] = new \Tusk\Inspection\Scope(null);
    }

    public function enterNode(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $this->scopes[] = new \Tusk\Inspection\Scope($node);
            return;
        }
        
        if ($node instanceof \PhpParser\Node\FunctionLike) {
            $this->scopes[] = new \Tusk\Inspection\Scope($node);
            $scope = end($this->scopes);
            foreach ($node->getParams() as $param) {
                $scope->addVar($param);
            }
            return;
        }

        $scope = end($this->scopes);
        
        if ($this->isQualified($node)) {
            if (!$scope->hasVar($node)) {
                $node->setAttribute(self::MARKER, true);
                $scope->addVar($node);
            } else {
                $scope->registerOn($node);
            }
        }
    }
    
    private function isQualified(\PhpParser\Node $node) : bool
    {
        $qualifies = $node instanceof \PhpParser\Node\Expr\PropertyFetch ||
            $node instanceof \PhpParser\Node\Stmt\Property;
        if (!$qualifies && $node instanceof \PhpParser\Node\Expr\Variable) {
            $qualifies = $node->getAttribute(TreeRelation::PARENT) != \PhpParser\Node\Expr\PropertyFetch::class;
        }
        return $qualifies;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\FunctionLike)
            array_pop($this->scopes);
    }

    
}
