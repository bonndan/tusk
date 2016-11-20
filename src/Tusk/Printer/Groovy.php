<?php

namespace Tusk\Printer;

/**
 * Stateful pretty printer for Groovy output.
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
     * @todo prevent multiple same imports
     */
    public function pStmt_Const(\PhpParser\Node\Stmt\Const_ $node)
    {
        $buffer = "import groovy.transform.Field" . PHP_EOL . '@Field ';

        //add type if one const
        if (count($node->consts) == 1) {
            if ($node->consts[0]->value instanceof \PhpParser\Node\Scalar\LNumber) {
                $buffer .= "Integer ";
                $buffer .= $this->p($node->consts[0]);
            }

            if ($node->consts[0]->value instanceof \PhpParser\Node\Scalar\String_) {
                $buffer .= "String ";
                $buffer .= $this->p($node->consts[0]);
            }

            return $buffer;
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
                return $buffer . $this->getType($tags[0]) . ' ' . $this->p($node->props[0]) . PHP_EOL;
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
        if ($typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Var_ || $typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Param || $typeObject instanceof \phpDocumentor\Reflection\DocBlock\Tags\Return_
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

    /**
     * Returns the corresponding package notation.
     * 
     * @param string $namespaced
     * @return string
     */
    public static function asPackage(string $namespaced): string
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
            return 'package ' . self::asPackage($this->p($node->name)) . "\n" . $this->pStmts($node->stmts, false);
        } else {
            return 'package ' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
                . ' {' . $this->pStmts($node->stmts) . "\n" . '}';
        }
    }
    
    public function pStmt_Throw(\PhpParser\Node\Stmt\Throw_ $node)
    {
        return 'throw ' . $this->p($node->expr);
    }

    public function p(\PhpParser\Node $node)
    {
        //var_dump($node);
        return $this->{'p' . $node->getType()}($node);
    }

    public function pComments(array $comments)
    {
        $formattedComments = [];

        foreach ($comments as $comment) {
            $c = $comment->getReformattedText();
            if ($c[0] == '#')
                $c = '//' . substr($c, 1);
            $formattedComments[] = $c;
        }

        return implode("\n", $formattedComments);
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
        return $this->pStmt_FunctionLike($node);
    }

    public function pStmt_Function(\PhpParser\Node\Stmt\Function_ $node)
    {
        return $this->pStmt_FunctionLike($node);
    }

    private function pStmt_FunctionLike(\PhpParser\Node\FunctionLike $node)
    {
        $tags = $this->getNodeDocBlockTags($node);
        foreach ($tags as $tag) {

            if ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tags\Param) {
                foreach ($node->params as $param) {
                    /* @var $param \PhpParser\Node\Param */
                    if ($param->name == $tag->getVariableName()) {
                        if (!$param->type)
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
            $buffer = (null !== $node->returnType ? $this->pType($node->returnType) . ' ' : 'def ') . $node->name;
        }


        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $buffer = $this->pModifiers($node->type) . $buffer;
        }

        return $buffer
            . '(' . $this->pCommaSeparated($node->params) . ')'
            . (null !== $node->stmts ? "\n" . '{' . $this->pStmts($node->stmts) . "\n" . '}' . PHP_EOL : PHP_EOL);
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

    protected function pType($node)
    {
        if (is_string($node)) {
            if ($node == 'bool')
                return 'boolean';
            if ($node == 'string')
                return 'String';
            if ($node == 'type') //bad doc comment
                return 'def';
            if ($node == 'array') /* @todo losing info here */
                return $this->getTodo('Collection|Map') . ' def';

            return $node;
        }

        return $this->p($node);
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
        $keyHandling = '';
        $valueVar = $this->p($node->valueVar);
        if (null !== $node->keyVar) {
            $valueVar = 'entry';
            $keyHandling = "\nif (" . $valueVar . " in Map.Entry) {\n"
                . $this->p($node->keyVar) ." = entry.key\n" . $this->p($node->valueVar) . " = entry.value"
                . "\n}";
        }

        return 'for (' . $valueVar . ' in ' . $this->p($node->expr) . ') {'
            . $keyHandling 
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
        if (property_exists($parent, "stmts") && $parent->stmts)
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
        return self::asPackage(parent::pName($node));
    }

    public function pName_FullyQualified(\PhpParser\Node\Name\FullyQualified $node): string
    {
        return self::asPackage(parent::pName_FullyQualified($node));
    }

    public function pExpr_StaticCall(\PhpParser\Node\Expr\StaticCall $node)
    {
        $buffer = '';
        if ($node->class instanceof \PhpParser\Node\Name && $node->class->parts[0] == 'parent')
            $buffer .= 'super.';
        elseif ($node->class instanceof \PhpParser\Node\Name && $node->class->parts[0] == 'self')
            $buffer .= '';
        else
            $buffer .= $this->pDereferenceLhs($node->class) . '.';

        return $buffer
            . ($node->name instanceof Expr ? ($node->name instanceof Expr\Variable ? $this->p($node->name) : '{' . $this->p($node->name) . '}') : $node->name)
            . '(' . $this->pCommaSeparated($node->args) . ')';
    }

    public function pExpr_StaticPropertyFetch(\PhpParser\Node\Expr\StaticPropertyFetch $node)
    {
        if ($node->class instanceof \PhpParser\Node\Name && $node->class->parts[0] == 'self')
            $buffer = '';
        else
            $buffer = $this->pDereferenceLhs($node->class) . '.';
        return $buffer . $this->pObjectProperty($node->name);
    }

    public function pExpr_MethodCall(\PhpParser\Node\Expr\MethodCall $node)
    {
        $buffer = ($node->var->name == 'this') ? '' : $this->pDereferenceLhs($node->var) . '.';
        return $buffer . $this->pObjectProperty($node->name)
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
            . self::asPackage($this->pCommaSeparated($node->uses));
    }

    /**
     * @todo println is not echo!
     */
    public function pStmt_Echo(\PhpParser\Node\Stmt\Echo_ $node)
    {
        return 'println ' . $this->pImplode($node->exprs, ' + ');
        ;
    }

    public function pExpr_ClassConstFetch(\PhpParser\Node\Expr\ClassConstFetch $node)
    {
        $class = $node->class == 'self' ? '' : $this->p($node->class) . '.';
        return  $class . $node->name;
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

    public function pExpr_BinaryOp_LogicalAnd(\PhpParser\Node\Expr\BinaryOp\LogicalAnd $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalAnd', $node->left, ' && ', $node->right);
    }

    public function pExpr_BinaryOp_LogicalOr(\PhpParser\Node\Expr\BinaryOp\LogicalOr $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalOr', $node->left, ' || ', $node->right);
    }

    public function pExpr_BinaryOp_LogicalXor(\PhpParser\Node\Expr\BinaryOp\LogicalXor $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalXor', $node->left, ' ^ ', $node->right);
    }

    public function pExpr_Array(\PhpParser\Node\Expr\Array_ $node)
    {
        return '[' . $this->pCommaSeparated($node->items) . ']';
    }

    public function pExpr_ArrayItem(\PhpParser\Node\Expr\ArrayItem $node)
    {

        $key = null;
        if (null !== $node->key) {
            if ($node->key instanceof \PhpParser\Node\Expr\ClassConstFetch) {
                $key = '(' . $this->pExpr_ClassConstFetch($node->key) . ')';
            } elseif ($node->key instanceof \PhpParser\Node\Expr\Variable) {
                $key = '(' . $node->key->name . ')';
            } elseif ($node->key instanceof \PhpParser\Node\Scalar\String_) {
                $key = $node->key->value;
            } elseif ($node->key instanceof \PhpParser\Node\Expr\FuncCall || $node->key instanceof \PhpParser\Node\Expr\MethodCall
            ) {
                $key = '(' . $this->p($node->key) . ')';
            } else {
                $key = $this->p($node->key);
            }
        }

        $key = (null !== $key ? $key . ': ' : '');
        return $key . $this->p($node->value);
    }

    public function pExpr_Yield(\PhpParser\Node\Expr\Yield_ $node)
    {
        return $this->getException(parent::pExpr_Yield($node) . ' is not supported');
    }

    public function pExpr_Exit(\PhpParser\Node\Expr\Exit_ $node)
    {
        $kind = $node->getAttribute('kind', \PhpParser\Node\Expr\Exit_::KIND_DIE);
        if ($kind === \PhpParser\Node\Expr\Exit_::KIND_EXIT)
            return "System.exit(" . (null !== $node->expr ? $this->p($node->expr) : '') . ")";

        return "throw new GroovyException(" . (null !== $node->expr ? $this->p($node->expr) : "'die'") . ")";
    }

    public function pExpr_Cast_Array(\PhpParser\Node\Expr\Cast\Array_ $node)
    {
        return $this->pPostfixOp('Expr_Cast_Array', $node->expr, ' as Object[]');
    }

    public function pExpr_Cast_Unset(\PhpParser\Node\Expr\Cast\Unset_ $node)
    {
        return "null";
    }

    public function pStmt_Unset(\PhpParser\Node\Stmt\Unset_ $node)
    {
        $buffer = '';
        foreach ($node->vars as $expr) {
            $buffer .= $this->p($expr) . ' = null' . PHP_EOL;
        }

        return $buffer;
    }

    public function pScalar_String(\PhpParser\Node\Scalar\String_ $node)
    {
        $isMultiline = $node->getAttribute("startLine") < $node->getAttribute('endLine');
        $kind = $node->getAttribute('kind', \PhpParser\Node\Scalar\String_::KIND_SINGLE_QUOTED);
        switch ($kind) {
            case \PhpParser\Node\Scalar\String_::KIND_NOWDOC:
            case \PhpParser\Node\Scalar\String_::KIND_HEREDOC:
                $label = $node->getAttribute('docLabel');
                if ($label && !$this->containsEndLabel($node->value, $label)) {
                    if ($node->value === '') {
                        return $this->pNoIndent("''");
                    }

                    return $this->pNoIndent('"""' . PHP_EOL . $node->value . PHP_EOL . '"""');
                }
            /* break missing intentionally */
            case \PhpParser\Node\Scalar\String_::KIND_SINGLE_QUOTED:
                $escaped = $this->pNoIndent(addcslashes($node->value, '\'\\'));
                if (substr($escaped, -1) == '"')
                    $escaped .= ' '; //adding a whitespace if last char is "
                return $isMultiline ? '"""' . $escaped . '"""' : '\'' . $escaped. '\'';

            case \PhpParser\Node\Scalar\String_::KIND_DOUBLE_QUOTED:
                $escaped = $this->escapeString($node->value, '"');
                if (substr($escaped, -1) == '"')
                    $escaped .= ' '; //adding a whitespace if last char is "
                return $isMultiline ? '"""' . $escaped . '"""' : '"' . $escaped . '"';
        }
        throw new \Exception('Invalid string kind');
    }

    public function pExpr_Closure(\PhpParser\Node\Expr\Closure $node)
    {
        if ($node->static)
            return $this->getException("Static closure is not supported.");
        if (!empty($node->uses))
            return $this->getException("Closure use() is not supported.");

        return '{ ' . $this->pCommaSeparated($node->params) . ' ->' . PHP_EOL
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

    public function pStmt_Return(\PhpParser\Node\Stmt\Return_ $node)
    {
        return 'return' . (null !== $node->expr ? ' ' . $this->p($node->expr) : '');
    }

    public function pScalar_MagicConst_Class(\PhpParser\Node\Scalar\MagicConst\Class_ $node)
    {
        return $this->getTodo('__CLASS__ was used') . " this.getClass().getName()";
    }

    public function pScalar_MagicConst_Dir(\PhpParser\Node\Scalar\MagicConst\Dir $node)
    {
        return $this->getTodo('__DIR__ was used') . " getClass().getProtectionDomain().getCodeSource().getLocation().getPath()";
    }

    public function pScalar_MagicConst_Line(\PhpParser\Node\Scalar\MagicConst\Line $node)
    {
        return $this->getTodo('__LINE__ was used') . " 1";
    }

    public function pScalar_MagicConst_Method(\PhpParser\Node\Scalar\MagicConst\Method $node)
    {
        return $this->getTodo('__METHOD__ was used') . " 'METHOD'";
    }

    public function pScalar_MagicConst_Function(\PhpParser\Node\Scalar\MagicConst\Function_ $node)
    {
        return $this->getTodo('__FUNCTION__ was used') . " 'FUNCTION'";
    }

    public function pScalar_MagicConst_File(\PhpParser\Node\Scalar\MagicConst\File $node)
    {
        return $this->getTodo('__FILE__ was used') . " getClass().getProtectionDomain().getCodeSource().getLocation().getPath()";
    }

    public function pExpr_Empty(\PhpParser\Node\Expr\Empty_ $node)
    {
        return $this->p($node->expr);
    }

    public function pExpr_Eval(\PhpParser\Node\Expr\Eval_ $node)
    {
        return $this->getTodo('better find a different solution than eval') . ' evaluate(' . $this->p($node->expr) . ')';
    }

    public function pStmt_Goto(\PhpParser\Node\Stmt\Goto_ $node)
    {
        return $this->getException("goto is not supported.");
    }

    public function pStmt_Global(\PhpParser\Node\Stmt\Global_ $node)
    {
        return $this->getException("global is not supported, maybe try public static class members.");
    }

    public function pStmt_ElseIf(\PhpParser\Node\Stmt\ElseIf_ $node)
    {
        return ' else if (' . $this->p($node->cond) . ') {'
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

    private function pUseType($type)
    {
        return $type === \PhpParser\Node\Stmt\Use_::TYPE_FUNCTION ? 'static ' :  '';
    }

    protected function pObjectProperty($node)
    {
        if ($node instanceof \PhpParser\Node\Expr) {
            return '"$' . $this->p($node) . '"';
        } else {
            return $node;
        }
    }
    
    private function getState() : \Tusk\State
    {
        if (!isset($this->options['state']))
            throw new \RuntimeException('No state option present.');
        
        return $this->options['state'];
    }

    private function getException(string $unsupported)
    {
        return "throw new GroovyException('$unsupported')";
    }

    private function getTodo(string $todo)
    {
        return '/* TODO ' . $todo . ' */';
    }

    private function throwError(\PhpParser\Node $node)
    {
        throw new \PhpParser\Error("ArrayItem conversion error on " . serialize($node) . "in " . $this->getState()->getFilename(), $node->getAttributes());
    }

}
