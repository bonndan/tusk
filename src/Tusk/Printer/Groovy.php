<?php

namespace Tusk\Printer;

/**
 * Description of Groovy
 *
 * 
 */
class Groovy extends \PhpParser\PrettyPrinter\Standard
{
    public function pStmt_ClassConst(\PhpParser\Node\Stmt\ClassConst $node)
    {
        $buffer = 'public final ';
        
        //add type if one const
        if (count($node->consts) == 1) {
            if ($node->consts[0]->value instanceof \PhpParser\Node\Scalar\LNumber) {
                $buffer .= "Integer ";
                return $buffer . $this->p($node->consts[0]);
            }
            
            if ($node->consts[0]->value instanceof \PhpParser\Node\Scalar\String_) {
                $buffer .= "String ";
                return $buffer . $this->p($node->consts[0]);
            }
        }
        
        //anything else
        return $buffer . $this->pCommaSeparated($node->consts) . ';';
    }
    
    /**
     * Assigns the class name to the cosntructor function.
     * 
     * @param \PhpParser\Node\Stmt\Class_ $node
     * @return type
     */
    public function pStmt_Class(\PhpParser\Node\Stmt\Class_ $node)
    {   
        foreach ($node->stmts as $st) {
            if ($st instanceof \PhpParser\Node\Stmt\ClassMethod && $st->name == '__construct') {
                $st->name = $node->name;
                $st->isConstructor = true;
            }
        }
        
        return parent::pStmt_Class($node);
    }
    
    public function __pConst(\PhpParser\Node\Const_ $node) 
    {    
        return $node->name . ' = ' . $this->p($node->value);
    }
    
    /**
     * @todo evaluate $node->attributes->comments[0]
     */
    public function pStmt_Property(\PhpParser\Node\Stmt\Property $node)
    {   
        $buffer = (0 === $node->type ? 'var ' : $this->pModifiers($node->type));
        if (count($node->props) == 1) {
            if ($node->props[0]->default instanceof \PhpParser\Node\Scalar\LNumber) {
                $buffer .= "Integer ";
                return $buffer . $this->p($node->props[0]) . PHP_EOL;
            }
            
            if ($node->props[0]->default instanceof \PhpParser\Node\Scalar\String_) {
                $buffer .= "String ";
                return $buffer . $this->p($node->props[0]) . PHP_EOL;
            }
        }
        
        return parent::pStmt_Property($node);
    }
    
    /**
     * @todo namespace edge cases
     */
    public function pStmt_Namespace(\PhpParser\Node\Stmt\Namespace_ $node)
    {
        if ($this->canUseSemicolonNamespaces) {
            return 'package ' . $this->p($node->name) . ';' . "\n" . $this->pStmts($node->stmts, false);
        } else {
            return 'package ' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
                 . ' {' . $this->pStmts($node->stmts) . "\n" . '}';
        }
    }
    
    /**
     * Ensures double quotes are used.
     * 
     * @param \PhpParser\Node\Const_ $node
     * @return string
     * @todo escape double quotes inside single quotes.
     */
    public function pScalar_String(\PhpParser\Node\Scalar\String_ $node) 
    {
        $node->setAttribute('kind', \PhpParser\Node\Scalar\String_::KIND_DOUBLE_QUOTED);
        return parent::pScalar_String($node);
    }
    
    public function p(\PhpParser\Node $node)
    {
        //var_dump('p' . $node->getType());
        return parent::p($node);
    }
    
    /**
     * Removes "$" from properties.
     * 
     * @param \PhpParser\Node\Stmt\PropertyProperty $node
     * @return type
     */
    public function pStmt_PropertyProperty(\PhpParser\Node\Stmt\PropertyProperty $node) {
        return $node->name
             . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }
    
    /**
     * Writes methods, special constructor handling.
     * 
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return type
     * @throws \RuntimeException
     */
    public function pStmt_ClassMethod(\PhpParser\Node\Stmt\ClassMethod $node)
    {
        if (isset($node->isConstructor) && $node->isConstructor) {
            $buffer = $node->name;
        } else {
            $buffer = 'def ' . $node->name;
        }
        
        return $this->pModifiers($node->type)
             . $buffer
             . '(' . $this->pCommaSeparated($node->params) . ')'
             . (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '')
             . (null !== $node->stmts
                ? "\n" . '{' . $this->pStmts($node->stmts) . "\n" . '}'
                : ';');
    }
    
    /**
     * Removing "$" and by-ref from params.
     * 
     * @param \PhpParser\Node\Param $node
     * @return type
     */
    public function pParam(\PhpParser\Node\Param $node)
    {
        return ($node->type ? $this->pType($node->type) . ' ' : '')
             . ($node->variadic ? '...' : '')
             . $node->name
             . ($node->default ? ' = ' . $this->p($node->default) : '');
    }
    
    /**
     * @todo handle expressions
     */
    public function pExpr_Variable(\PhpParser\Node\Expr\Variable $node)
    {
        if ($node->name instanceof Expr) {
            return 'unsupported dynamic expression {' . $this->p($node->name) . '}';
        } else {
            return $node->name;
        }
    }
    
    public function pExpr_Assign(\PhpParser\Node\Expr\Assign $node)
    {
        return parent::pExpr_Assign($node);
    }
    
    public function pExpr_PropertyFetch(\PhpParser\Node\Expr\PropertyFetch $node)
    {
        return $this->pDereferenceLhs($node->var) . '.' . $this->pObjectProperty($node->name);
    }
}
