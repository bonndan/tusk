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
                return $buffer . $type . ' ' . $this->p($node->props[0]) . PHP_EOL;


            $tags = $this->getNodeDocBlockTags($node);
            if (isset($tags[0])) {
                return $buffer . $this->getType($tags[0]) .' ' . $this->p($node->props[0]) . PHP_EOL;
            }
        }

        return $buffer . $this->pCommaSeparated($node->props);
    }

    private function getType($typeObject): string
    {
        if ($typeObject instanceof \PhpParser\Node\Scalar\LNumber) {
            return "Integer";
        }

        if ($typeObject instanceof \PhpParser\Node\Scalar\String_) {
            return "String";
        }

        /**
         * @todo support key-value type annotation which is supported by phpdocumentor
         */
        if ($typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Var_
            || $typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Param
            || $typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Return_
        ) {

            $raw = (string) $typeObject->getType(); 
            $buffer = (strpos($raw, '[]') !== false) ? "[]" : '';
            switch (str_replace('[]', '', $raw)) {
                case "string": return "String" . $buffer;
                case "int": return "Integer" . $buffer;
                case "array": return "def";
                default : return $this->asPackage($raw) . '';
            }
        }

        return '';
    }

    private function asPackage(string $namespaced): string
    {
        return ltrim(str_replace("\\", ".", $namespaced), '.');
    }

    private function getNodeDocBlockTags(\PhpParser\Node\Stmt $node): array
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
    {        //var_dump($node);
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
     */
    public function pStmt_ClassMethod(\PhpParser\Node\Stmt\ClassMethod $node)
    {
        if ($node->name == '__call') {
            $node->name = "methodMissing";
            $node->params = [];
            $node->params[0] = new \PhpParser\Node\Param('name');
            $node->params[0]->type = 'String';
            $node->params[1] = new \PhpParser\Node\Param('arguments');
            $node->params[1]->type = 'def';
        }



        $tags = $this->getNodeDocBlockTags($node);
        foreach ($tags as $tag) {

            if ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tags\Param) {
                foreach ($node->params as $param) {
                    if ($param->name == $tag->getVariableName()) {
                        $param->type = $this->getType($tag);
                    }
                }
            }

            if ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tags\Return_) {
                $type = $this->getType($tag);             
                if ($type)
                    $node->returnType = $type;
            }
        }

        if (isset($node->isConstructor) && $node->isConstructor) {
            $buffer = $node->name;
        } else {
            $buffer = (null !== $node->returnType ? $this->pType($node->returnType) . ' ': 'def ') . $node->name;
        }
        
        return $this->pModifiers($node->type)
            . $buffer
            . '(' . $this->pCommaSeparated($node->params) . ')'
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

    public function pExpr_Variable(\PhpParser\Node\Expr\Variable $node)
    {
        if ($node->name instanceof \PhpParser\Node\Expr) {
            return '"$' . $this->p($node->name) . '"';
        } else {
            return $node->name;
        }
    }

    public function pExpr_Assign(\PhpParser\Node\Expr\Assign $node)
    {
        return parent::pExpr_Assign($node);
    }

    public function pExpr_BinaryOp_Concat(\PhpParser\Node\Expr\BinaryOp\Concat $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Concat', $node->left, ' + ', $node->right);
    }

    public function pExpr_AssignOp_Concat(\PhpParser\Node\Expr\AssignOp\Concat $node)
    {
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
            return $this->p($node->expr) . '.eachWithIndex { ' .
                $this->p($node->valueVar) . ", " . $this->p($node->keyVar) . ' -> ' .
                $this->pStmts($node->stmts) . "\n" . '}';
        }

        return 'for (' .
            $this->p($node->valueVar) .
            ' in ' . $this->p($node->expr) .
            ') {'
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

    protected function preprocessNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->replaceTraits($node);
        }

        return parent::preprocessNodes($nodes);
    }

    /**
     * @todo trait predence parameters require knowledge about trait impls.
     * @param \PhpParser\Node $parent
     * @return void
     */
    private function replaceTraits(\PhpParser\Node $parent): void
    {
        $traits = [];
        $adaptations = [];
        if ($parent instanceof \PhpParser\Node\Stmt\Class_) {
            foreach ($parent->stmts as $key => $node) {
                if ($node instanceof \PhpParser\Node\Stmt\TraitUse) {

                    foreach ($node->adaptations as $adapt) {
                        $adaptations[] = $adapt;
                    }

                    foreach ($node->traits as $name) {
                        $traits[] = implode('.', $name->parts);
                    }
                    unset($parent->stmts[$key]);
                }
            }

            //add implements for each trait
            foreach ($traits as $name) {
                $parent->implements[] = new \PhpParser\Node\Name\FullyQualified($name);
            }

            //add trait conflict resolution
            foreach ($adaptations as $a) {
                $parent->stmts[] = $this->adaptoToClassMethod($a);
            }

            return;
        }
        if (property_exists($parent, "stmts"))
            foreach ($parent->stmts as $key => $node) {
                $this->replaceTraits($node);
            }
    }

    private function adaptoToClassMethod(\PhpParser\Node\Stmt\TraitUseAdaptation\Precedence $prec): \PhpParser\Node\Stmt\ClassMethod
    {
        $func = new \PhpParser\Node\Stmt\ClassMethod($prec->method);
        $func->stmts[] = new \PhpParser\Node\Expr\FuncCall(
            new \PhpParser\Node\Name([$prec->trait . ".super." . $prec->method])
        );
        return $func;
    }

    public function pName(\PhpParser\Node\Name $node)
    {
        return $this->asPackage(parent::pName($node));
    }
    
    public function pName_FullyQualified(\PhpParser\Node\Name\FullyQualified $node): string
    {
        return $this->asPackage(parent::pName_FullyQualified($node));
    }

    public function pExpr_StaticCall(\PhpParser\Node\Expr\StaticCall $node)
    {
        $buffer = '';
        if ($node->class instanceof \PhpParser\Node\Name && $node->class->parts[0] == 'parent')
            $buffer .= 'super';
        else
            $buffer .= $this->pDereferenceLhs($node->class);

        return $buffer . '.'
            . ($node->name instanceof Expr ? ($node->name instanceof Expr\Variable ? $this->p($node->name) : '{' . $this->p($node->name) . '}') : $node->name)
            . '(' . $this->pCommaSeparated($node->args) . ')';
    }

    public function pExpr_StaticPropertyFetch(\PhpParser\Node\Expr\StaticPropertyFetch $node)
    {
        return $this->pDereferenceLhs($node->class) . '.' . $this->pObjectProperty($node->name);
    }

    public function pExpr_MethodCall(\PhpParser\Node\Expr\MethodCall $node)
    {
        return $this->pDereferenceLhs($node->var) . '.' . $this->pObjectProperty($node->name)
            . '(' . $this->pCommaSeparated($node->args) . ')';
    }
    
    public function pStmt_Catch(\PhpParser\Node\Stmt\Catch_ $node)
    {
        return ' catch (' . $this->p($node->type) . ' ' . $node->var . ') {'
             . $this->pStmts($node->stmts) . "\n" . '}';
    }
    
    public function pStmt_Use(\PhpParser\Node\Stmt\Use_ $node)
    {
        return 'import ' . $this->pUseType($node->type)
             . $this->asPackage($this->pCommaSeparated($node->uses));
    }
    
    /**
     * @todo println is not echo!
     */
    public function pStmt_Echo(\PhpParser\Node\Stmt\Echo_ $node)
    {
        return 'println ' . $this->pImplode($node->exprs, ' + ');;
    }
    
    public function pExpr_ClassConstFetch(\PhpParser\Node\Expr\ClassConstFetch $node)
    {
         return $this->p($node->class) . '.' . $node->name;
    }
    
    /**
     * @todo use "is" if object comparison is wanted
     */
    public function pExpr_BinaryOp_Identical(\PhpParser\Node\Expr\BinaryOp\Identical $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Identical', $node->left, ' == ', $node->right);
    }
    
    public function pExpr_BinaryOp_NotIdentical(\PhpParser\Node\Expr\BinaryOp\NotIdentical $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_NotIdentical', $node->left, ' != ', $node->right);
    }
    
    public function pExpr_BinaryOp_Coalesce(\PhpParser\Node\Expr\BinaryOp\Coalesce $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Coalesce', $node->left, ' ?: ', $node->right); 
    }
    
    /**
     * @todo seek equivalent in groovy. import static?
     */
    private function pUseType($type) {
        return $type === \PhpParser\Node\Stmt\Use_::TYPE_FUNCTION ? 'function '
            : ($type === \PhpParser\Node\Stmt\Use_::TYPE_CONSTANT ? 'const ' : '');
    }

    protected function pObjectProperty($node)
    {
        if ($node instanceof \PhpParser\Node\Expr) {
            return '"$' . $this->p($node) . '"';
        } else {
            return $node;
        }
    }

}
