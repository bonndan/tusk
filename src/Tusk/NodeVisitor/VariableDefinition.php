<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\For_;
use PhpParser\NodeVisitorAbstract;
use Tusk\Inspection\Scope;

/**
 * Finds variable definitions in scopes nodes and marks them.
 * 
 * 
 */
class VariableDefinition extends NodeVisitorAbstract
{

    /**
     * @var Scope[]
     */
    private $scopes;

    public function __construct()
    {
        $this->scopes[] = new Scope(null);
    }

    public function enterNode(Node $node)
    {
        if (Scope::hasOwnScope($node)) {
            $scope = end($this->scopes);
            $newScope = new Scope($node, $scope);
            $this->scopes[] = $newScope;
            
            if ($node instanceof Node\FunctionLike) {
                foreach ($node->getParams() as $param) {
                    $newScope->addVar($param);
                }
            }
        
            if (isset($node->cond) && $node->cond instanceof Assign) {
                $node->cond->setAttribute(Scope::CONDITION, true);
            }
            
            if (isset($node->init) && $node->init[0] instanceof Assign) {
                $node->init[0]->setAttribute(Scope::CONDITION, true);
            }
            
            if ($node instanceof Foreach_) {
                if ($node->keyVar instanceof Variable) {
                    $newScope->addVar($node->keyVar);
                    //$node->keyVar->setAttribute(Scope::VAR_DEFINITION, false);
                }
                $node->expr->setAttribute(Scope::CONDITION, true);
            }
            
            if ($node instanceof For_) {
                if ($node->init[0] instanceof Assign) {
                    $newScope->addVar($node->init[0]->var);
                    //$node->init[0]->var->setAttribute(Scope::VAR_DEFINITION, false);
                }
            }
            
            return;
        }

        $scope = end($this->scopes); /* @var $scope Scope */

        if ($this->isQualified($node)) {
            $scope->addVar($node);
        }
    }

    private function isQualified(Node $node): bool
    {
        $qualifies = $node instanceof PropertyFetch || $node instanceof Property;
        if (!$qualifies && $node instanceof Variable) {
            $qualifies = $node->getAttribute(TreeRelation::PARENT) != PropertyFetch::class;
        }
        return $qualifies;
    }

    public function leaveNode(Node $node)
    {
        if (Scope::hasOwnScope($node))
            array_pop($this->scopes);
    }

}
