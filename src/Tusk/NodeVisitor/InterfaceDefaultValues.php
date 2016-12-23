<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;

/**
 * Checks interfaces for methods with default params and overloads the methods
 * to avoid default params.
 * 
 * 
 * 
 */
class InterfaceDefaultValues extends NodeVisitorAbstract implements InfluencingVisitor
{

    public function enterNode(Node $node)
    {
        if (!$node instanceof Interface_)
            return;

        $modified = false;
        foreach ($node->stmts as $st) {
            if (!$st instanceof FunctionLike) {
                continue;
            }

            $dup = $this->getOverloadings($st);
            if (!empty($dup)) {
                $modified = true;
                $node->stmts = $dup;
            }
        }

        if (!$modified) {
            return $node;
        }
    }

    private function getOverloadings(Node\Stmt\ClassMethod $st): array
    {
        $dups = [];
        foreach ($st->getParams() as $param) {
            if ($param->default)
                $dups = array_merge($dups, $this->getOverloaded($st, $param));
        }

        return $dups;
    }

    private function getOverloaded(Node\Stmt\ClassMethod $st, Node\Param $undefault)
    {
        $func = clone $st;
        $func->params = [];
        foreach ($st->getParams() as $p) {
            $param = clone $p;
            if ($param->name == $undefault->name) {
                break;
            }
            $param->default = null;
            $func->params[] = $param;
        }
        
        $funcWith = clone $st;
        $funcWith->params = [];
        foreach ($st->getParams() as $p) {
            $param = clone $p;
            $param->default = null;
            $funcWith->params[] = $param;
            if ($param->name == $undefault->name) {
                break;
            }
        }
        
        return [
            $this->signatureCheck($func) =>$func, 
            $this->signatureCheck($funcWith) =>$funcWith, 
            ];
    }

    /**
     * Ensures that no duplicate signatures exist.
     * 
     * @param \PhpParser\Node\Stmt\ClassMethod $method
     * @return string
     */
    private function signatureCheck(Node\Stmt\ClassMethod $method) : string
    {
        $check = '';
        foreach ($method->getParams() as $p) {
            $check .= $p->name;
        }
        
        return $check;
    }
}
