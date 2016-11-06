<?php

namespace Tusk\Printer;

/**
 * Description of Groovy
 *
 * 
 */
class Groovy extends \PhpParser\PrettyPrinter\Standard
{

    /**
     * @var \phpDocumentor\Reflection\DocBlockFactory
     */
    private $docblockFactory;

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->docblockFactory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
    }

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

    public function pStmt_Property(\PhpParser\Node\Stmt\Property $node)
    {
        $buffer = (0 === $node->type ? 'var ' : $this->pModifiers($node->type));

        if (count($node->props) == 1) {

            $type = $this->getType($node->props[0]->default);
            if ($type)
                return $buffer . $type . $this->p($node->props[0]) . PHP_EOL;


            $tags = $this->getNodeDocBlockTags($node);
            if (isset($tags[0])) {
                return $buffer . $this->getType($tags[0]) . $this->p($node->props[0]) . PHP_EOL;
            }
        }

        return $buffer . $this->pCommaSeparated($node->props);
    }

    private function getType($typeObject): string
    {
        if ($typeObject instanceof \PhpParser\Node\Scalar\LNumber) {
            return "Integer ";
        }

        if ($typeObject instanceof \PhpParser\Node\Scalar\String_) {
            return "String ";
        }

        /**
         * @todo support key-value type annotation which is supported by phpdocumentor
         */
        if ($typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Var_) {

            $raw = (string) $typeObject->getType();
            $buffer = (strpos($raw, '[]') !== false) ? "[] " : ' ';

            switch (str_replace('[]', '', $raw)) {
                case "string": return "String" . $buffer;
                case "int": return "Integer" . $buffer;
                default : return $this->asPackage($raw) . ' ';
            }
        }

        return '';
    }

    private function asPackage(string $namespaced): string
    {
        return ltrim(str_replace("\\", ".", $namespaced), '.');
    }

    private function getNodeDocBlockTags(\PhpParser\Node\Stmt\Property $node): array
    {
        $comments = $node->getAttributes('comments');
        if ($comments && isset($comments['comments'][0])) {
            $doc = $comments['comments'][0]; /* @var $doc PhpParser\Comment\Doc */
            $docblock = $this->docblockFactory->create($doc->getText());
            return $docblock->getTags();
        }

        return [];
    }

    /**
     * @todo namespace edge cases
     */
    public function pStmt_Namespace(\PhpParser\Node\Stmt\Namespace_ $node)
    {
        if ($this->canUseSemicolonNamespaces) {
            return 'package ' . $this->asPackage($this->p($node->name)) . "\n" . $this->pStmts($node->stmts, false);
        } else {
            return 'package ' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
                . ' {' . $this->pStmts($node->stmts) . "\n" . '}';
        }
    }

    public function p(\PhpParser\Node $node)
    {        //var_dump('p' . $node->getType());
        return $this->{'p' . $node->getType()}($node);
    }

    /**
     * Removes "$" from properties.
     * 
     * @param \PhpParser\Node\Stmt\PropertyProperty $node
     * @return type
     */
    public function pStmt_PropertyProperty(\PhpParser\Node\Stmt\PropertyProperty $node)
    {
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
            . (null !== $node->stmts ? "\n" . '{' . $this->pStmts($node->stmts) . "\n" . '}' : ';');
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
    
    public function pExpr_BinaryOp_Concat(\PhpParser\Node\Expr\BinaryOp\Concat $node) {
        return $this->pInfixOp('Expr_BinaryOp_Concat', $node->left, ' + ', $node->right);
    }
    
    public function pExpr_AssignOp_Concat(\PhpParser\Node\Expr\AssignOp\Concat $node) {
        return $this->pInfixOp('Expr_AssignOp_Concat', $node->var, ' += ', $node->expr);
    }

    public function pExpr_PropertyFetch(\PhpParser\Node\Expr\PropertyFetch $node)
    {
        return $this->pDereferenceLhs($node->var) . '.' . $this->pObjectProperty($node->name);
    }

    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node[] $nodes  Array of nodes
     * @param bool   $indent Whether to indent the printed nodes
     *
     * @return string Pretty printed statements
     */
    protected function pStmts(array $nodes, $indent = true)
    {
        $result = '';
        foreach ($nodes as $node) {
            $comments = $node->getAttribute('comments', array());
            if ($comments) {
                $result .= "\n" . $this->pComments($comments);
                if ($node instanceof Stmt\Nop) {
                    continue;
                }
            }

            $result .= "\n" . $this->p($node);
        }

        if ($indent) {
            return preg_replace('~\n(?!$|' . $this->noIndentToken . ')~', "\n    ", $result);
        } else {
            return $result;
        }
    }

    public function pStmt_For(\PhpParser\Node\Stmt\For_ $node)
    {
        if ($node->init[0] instanceof \PhpParser\Node\Expr\Assign) {
            $node->init[0]->var->name = "int " . $node->init[0]->var->name;
        }

        return parent::pStmt_For($node);
    }

    public function pStmt_Foreach(\PhpParser\Node\Stmt\Foreach_ $node)
    {
        //use eachWithIndex for key-value pairs
        if (null !== $node->keyVar) {
            return $this->p($node->expr) .'.eachWithIndex { ' .
                $this->p($node->valueVar) . ", " . $this->p($node->keyVar) . ' -> '.
                $this->pStmts($node->stmts) . "\n" . '}';
        }

        return 'for (' .
            $this->p($node->valueVar) .
            ' in ' . $this->p($node->expr) .
            ') {'
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

}
