<?php

namespace Tusk\Printer;

use Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Comment\Doc;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_ as Array_2;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Concat as Concat2;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Array_;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\String_ as String_2;
use PhpParser\Node\Expr\Cast\Unset_ as Unset_2;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param as Param2;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst\Class_ as Class_2;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\MagicConst\Function_ as Function_2;
use PhpParser\Node\Scalar\MagicConst\Line;
use PhpParser\Node\Scalar\MagicConst\Method;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Return_ as Return_2;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use SebastianBergmann\CodeCoverage\Report\Xml\Node as Node2;
use Tusk\NodeVisitor\TreeRelation;
use Tusk\NodeVisitor\VariableDefinition;
use Tusk\State;

/**
 * Stateful pretty printer for Groovy output.
 * 
 *
 */
class Groovy extends Standard
{

    /**
     * @var DocBlockFactory
     */
    private $docblockFactory;

    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->docblockFactory = DocBlockFactory::createInstance();
    }

    public function pStmt_ClassConst(ClassConst $node)
    {
        $buffer = 'public final static ';

        //add type if one const
        if (count($node->consts) == 1) {
            if ($node->consts[0]->value instanceof LNumber) {
                $buffer .= "Integer ";
                return $buffer . $this->p($node->consts[0]);
            }

            if ($node->consts[0]->value instanceof String_) {
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
    public function pStmt_Const(Const_ $node)
    {
        $buffer = "import groovy.transform.Field" . PHP_EOL . '@Field ';

        //add type if one const
        if (count($node->consts) == 1) {
            if ($node->consts[0]->value instanceof LNumber) {
                $buffer .= "Integer ";
                $buffer .= $this->p($node->consts[0]);
            }

            if ($node->consts[0]->value instanceof String_) {
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
     * @param Class_ $node
     * @return type
     */
    public function pStmt_Class(Class_ $node)
    {
        foreach ($node->stmts as $st) {
            if ($st instanceof ClassMethod && $st->name == '__construct') {
                $st->name = $node->name;
                $st->isConstructor = true;
            }
        }

        return parent::pStmt_Class($node);
    }

    public function pStmt_Property(Property $node)
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
        if ($typeObject instanceof LNumber) {
            return "Integer";
        }

        if ($typeObject instanceof String_) {
            return "String";
        }

        /**
         * @todo support key-value type annotation which is supported by phpdocumentor
         */
        if ($typeObject instanceof Var_ 
            || $typeObject instanceof Param 
            || $typeObject instanceof Return_
        ) {

            $raw = (string) $typeObject->getType();
            if (strpos($raw, '|') !== false)
                return 'def';
            
            $buffer = (strpos($raw, '[]') !== false) ? "[]" : '';
            switch (str_replace('[]', '', $raw)) {
                case "string": return "String" . $buffer;
                case "int": return "Integer" . $buffer;
                case "array": return "def";
                case "mixed": return "def";
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

    private function getNodeDocBlockTags(Stmt $node): array
    {
        $comments = $node->getAttributes('comments');
        if ($comments && isset($comments['comments'][0])) {
            $doc = $comments['comments'][0]; /* @var $doc Doc */
            $docblock = $this->docblockFactory->create($doc->getText());
            return $docblock->getTags();
        }

        return [];
    }

    /**
     * @todo namespace edge cases
     */
    public function pStmt_Namespace(Namespace_ $node)
    {
        if ($this->canUseSemicolonNamespaces) {
            return 'package ' . self::asPackage($this->p($node->name)) . "\n" . $this->pStmts($node->stmts, false);
        } else {
            return 'package ' . (null !== $node->name ? ' ' . $this->p($node->name) : '')
                . ' {' . $this->pStmts($node->stmts) . "\n" . '}';
        }
    }
    
    public function pStmt_Throw(Throw_ $node)
    {
        return 'throw ' . $this->p($node->expr);
    }

    public function p(Node $node)
    {
        //var_dump(get_class($node));
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
     * @param PropertyProperty $node
     * @return type
     */
    public function pStmt_PropertyProperty(PropertyProperty $node)
    {
        return $node->name
            . (null !== $node->default ? ' = ' . $this->p($node->default) : '');
    }

    /**
     * Writes methods, special constructor handling.
     * 
     * @param ClassMethod $node
     * @return type
     */
    public function pStmt_ClassMethod(ClassMethod $node)
    {
        return $this->pStmt_FunctionLike($node);
    }

    public function pStmt_Function(Function_ $node)
    {
        return $this->pStmt_FunctionLike($node);
    }

    private function pStmt_FunctionLike(FunctionLike $node)
    {
        $tags = $this->getNodeDocBlockTags($node);
        foreach ($tags as $tag) {

            if ($tag instanceof Param) {
                foreach ($node->params as $param) {
                    /* @var $param Param2 */
                    if ($param->name == $tag->getVariableName()) {
                        if (!$param->type)
                            $param->type = $this->getType($tag);
                    }
                }
            }

            if ($tag instanceof Return_) {
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


        if ($node instanceof ClassMethod) {
            $buffer = $this->pModifiers($node->type) . $buffer;
        }

        return $buffer
            . '(' . $this->pCommaSeparated($node->params) . ')'
            . (null !== $node->stmts ? "\n" . '{' . $this->pStmts($node->stmts) . "\n" . '}' . PHP_EOL : PHP_EOL);
    }

    /**
     * Removing "$" and by-ref from params.
     * 
     * @param Param2 $node
     * @return type
     */
    public function pParam(Param2 $node)
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

    public function pExpr_Variable(Variable $node)
    {
        if ($node->name instanceof Expr) {
            return '"$' . $this->p($node->name) . '"';
        } else {
            return $node->name;
        }
    }

    public function pExpr_Assign(Assign $node)
    {
        $buffer = '';
        if ($node->var instanceof Variable) {
            $hasWhitespace = strpos($node->var->name, " ") !== false;
            $isDefition = $node->var->getAttribute(VariableDefinition::MARKER);
            if (!$hasWhitespace && $isDefition)
                $buffer = 'def ';
        }
        
        return $buffer . parent::pExpr_Assign($node);
    }

    public function pExpr_BinaryOp_Concat(Concat $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Concat', $node->left, ' + ', $node->right);
    }

    public function pExpr_AssignOp_Concat(Concat2 $node)
    {
        return $this->pInfixOp('Expr_AssignOp_Concat', $node->var, ' += ', $node->expr);
    }

    public function pExpr_PropertyFetch(PropertyFetch $node)
    {
        $scope = $node->getAttribute(\Tusk\Inspection\Scope::SCOPE);
        /* @var $scope \Tusk\Inspection\Scope */
        $obsolete = !$scope->hasVar($node);
        $buffer = $obsolete ? '' : $this->pDereferenceLhs($node->var) . '.' ;
        return $buffer . $this->pObjectProperty($node->name);
    }

    /**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node2[] $nodes  Array of nodes
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
                if ($node instanceof Nop) {
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

    public function pStmt_For(For_ $node)
    {
        if ($node->init[0] instanceof Assign) {
            $node->init[0]->var->name = "int " . $node->init[0]->var->name;
        }

        return parent::pStmt_For($node);
    }

    public function pStmt_Foreach(Foreach_ $node)
    {
        $keyHandling = '';
        $valueVar = $this->p($node->valueVar);
        if (null !== $node->keyVar) {
            $valueVar = 'entry';
            $keyHandling = "\n" . 
                "    " . ($node->keyVar->getAttribute(VariableDefinition::MARKER) ? 'def ' : '') 
                . $this->p($node->keyVar) ." = (" . $valueVar . " in Map.Entry) ? entry.key : " .$this->p($node->expr) . ".indexOf(entry)" 
                . $this->getTodo("unefficient"). "\n" . 
                "    " . ($node->keyVar->getAttribute(VariableDefinition::MARKER) ? 'def ' : '') 
                . $this->p($node->valueVar) ." = (" . $valueVar . " in Map.Entry) ? entry.value : entry\n";
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
     * @param Node $parent
     * @return void
     */
    private function replaceTraits(Node $parent): void
    {
        $traits = [];
        $adaptations = [];
        if ($parent instanceof Class_) {
            foreach ($parent->stmts as $key => $node) {
                if ($node instanceof TraitUse) {

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
                $parent->implements[] = new FullyQualified($name);
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

    private function adaptoToClassMethod(Precedence $prec): ClassMethod
    {
        $func = new ClassMethod($prec->method);
        $func->stmts[] = new FuncCall(
            new Name([$prec->trait . ".super." . $prec->method])
        );
        return $func;
    }

    public function pName(Name $node)
    {
        return self::asPackage(parent::pName($node));
    }

    public function pName_FullyQualified(FullyQualified $node): string
    {
        return self::asPackage(parent::pName_FullyQualified($node));
    }

    public function pExpr_StaticCall(StaticCall $node)
    {
        $buffer = '';
        if ($node->class instanceof Name && $node->class->parts[0] == 'parent')
            $buffer .= 'super.';
        elseif ($node->class instanceof Name && $node->class->parts[0] == 'self')
            $buffer .= '';
        else
            $buffer .= $this->pDereferenceLhs($node->class) . '.';

        return $buffer
            . ($node->name instanceof Expr ? ($node->name instanceof Variable ? $this->p($node->name) : '{' . $this->p($node->name) . '}') : $node->name)
            . '(' . $this->pCommaSeparated($node->args) . ')';
    }

    public function pExpr_StaticPropertyFetch(StaticPropertyFetch $node)
    {
        if ($node->class instanceof Name && $node->class->parts[0] == 'self')
            $buffer = '';
        else
            $buffer = $this->pDereferenceLhs($node->class) . '.';
        return $buffer . $this->pObjectProperty($node->name);
    }

    public function pExpr_MethodCall(MethodCall $node)
    {
        $buffer = ($node->var->name == 'this') ? '' : $this->pDereferenceLhs($node->var) . '.';
        return $buffer . $this->pObjectProperty($node->name)
            . '(' . $this->pCommaSeparated($node->args) . ')';
    }

    public function pStmt_Catch(Catch_ $node)
    {
        return ' catch (' . $this->pImplode($node->types, '|') . ' ' . $node->var . ') {'
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

    public function pStmt_Use(Use_ $node)
    {
        $buffer = '';
        foreach ($node->uses as $use) {
            $buffer .= $this->import($node->type, $use->name, ($use->name->getLast() !== $use->alias ? ' as ' .$use->alias : null));
        }

        return $buffer;
    }
    
    public function pStmt_GroupUse(GroupUse $node)
    {
        $buffer = "";
        foreach ($node->uses as $use) {
            $buffer .= $this->import($use->type, $node->prefix . '.' .$use->name);
        }
        
        return $buffer;
    }
    
    public function pStmt_UseUse(UseUse $node)
    {
        return $this->import($node->type, $node->name, ($node->name->getLast() !== $node->alias ? ' as ' .$node->alias : null));
    }

    private function import($type, $name, $alias = null) : string
    {
        return 'import ' . $this->pUseType($type) . self::asPackage($name) . $alias . PHP_EOL;
    }
    
    /**
     * @todo println is not echo!
     */
    public function pStmt_Echo(Echo_ $node)
    {
        return 'println ' . $this->pImplode($node->exprs, ' + ');
        ;
    }

    public function pExpr_ClassConstFetch(ClassConstFetch $node)
    {
        $class = $node->class == 'self' ? '' : $this->p($node->class) . '.';
        return  $class . $node->name;
    }

    /**
     * @todo use "is" if object comparison is wanted
     */
    public function pExpr_BinaryOp_Identical(Identical $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Identical', $node->left, ' == ', $node->right);
    }

    public function pExpr_BinaryOp_NotIdentical(NotIdentical $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_NotIdentical', $node->left, ' != ', $node->right);
    }

    public function pExpr_BinaryOp_Coalesce(Coalesce $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_Coalesce', $node->left, ' ?: ', $node->right);
    }

    public function pExpr_BinaryOp_LogicalAnd(LogicalAnd $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalAnd', $node->left, ' && ', $node->right);
    }

    public function pExpr_BinaryOp_LogicalOr(LogicalOr $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalOr', $node->left, ' || ', $node->right);
    }

    public function pExpr_BinaryOp_LogicalXor(LogicalXor $node)
    {
        return $this->pInfixOp('Expr_BinaryOp_LogicalXor', $node->left, ' ^ ', $node->right);
    }

    public function pExpr_Array(Array_2 $node)
    {
        return '[' . $this->pCommaSeparated($node->items) . ']';
    }

    public function pExpr_ArrayItem(ArrayItem $node)
    {

        $key = null;
        if (null !== $node->key) {
            if ($node->key instanceof ClassConstFetch) {
                $key = '(' . $this->pExpr_ClassConstFetch($node->key) . ')';
            } elseif ($node->key instanceof Variable) {
                $key = '(' . $node->key->name . ')';
            } elseif ($node->key instanceof String_ || $node->key instanceof LNumber) {
                $key = $node->key->value;
            } else {
                $key = '(' . $this->p($node->key) . ')';
            }
        }

        $key = (null !== $key ? $key . ': ' : '');
        return $key . $this->p($node->value);
    }

    public function pExpr_Yield(Yield_ $node)
    {
        return $this->getException(parent::pExpr_Yield($node) . ' is not supported');
    }

    public function pExpr_Exit(Exit_ $node)
    {
        $kind = $node->getAttribute('kind', Exit_::KIND_DIE);
        if ($kind === Exit_::KIND_EXIT)
            return "System.exit(" . (null !== $node->expr ? $this->p($node->expr) : '') . ")";

        return "throw new GroovyException(" . (null !== $node->expr ? $this->p($node->expr) : "'die'") . ")";
    }

    public function pExpr_Cast_Array(Array_ $node)
    {
        return $this->pPostfixOp('Expr_Cast_Array', $node->expr, ' as Object[]');
    }

    public function pExpr_Cast_Unset(Unset_2 $node)
    {
        return "null";
    }

    public function pStmt_Unset(Unset_ $node)
    {
        $buffer = '';
        foreach ($node->vars as $expr) {
            $buffer .= $this->p($expr) . ' = null' . PHP_EOL;
        }

        return $buffer;
    }

    public function pScalar_String(String_ $node)
    {
        $isMultiline = $node->getAttribute("startLine") < $node->getAttribute('endLine');
        $kind = $node->getAttribute('kind', String_::KIND_SINGLE_QUOTED);
        switch ($kind) {
            case String_::KIND_NOWDOC:
            case String_::KIND_HEREDOC:
                $label = $node->getAttribute('docLabel');
                if ($label && !$this->containsEndLabel($node->value, $label)) {
                    if ($node->value === '') {
                        return $this->pNoIndent("''");
                    }

                    return $this->pNoIndent('"""' . PHP_EOL . $node->value . PHP_EOL . '"""');
                }
            /* break missing intentionally */
            case String_::KIND_SINGLE_QUOTED:
                $escaped = $this->pNoIndent(addcslashes($node->value, '\'\\'));
                if (substr($escaped, -1) == '"')
                    $escaped .= ' '; //adding a whitespace if last char is "
                return $isMultiline ? '"""' . $escaped . '"""' : '\'' . $escaped. '\'';

            case String_::KIND_DOUBLE_QUOTED:
                $escaped = $this->escapeString($node->value, '"');
                if (substr($escaped, -1) == '"')
                    $escaped .= ' '; //adding a whitespace if last char is "
                return $isMultiline ? '"""' . $escaped . '"""' : '"' . $escaped . '"';
        }
        throw new Exception('Invalid string kind');
    }

    public function pExpr_Closure(Closure $node)
    {
        if ($node->static)
            return $this->getException("Static closure is not supported.");
        if (!empty($node->uses))
            return $this->getException("Closure use() is not supported.");

        return '{ ' . $this->pCommaSeparated($node->params) . ' ->' . PHP_EOL
            . $this->pStmts($node->stmts) . "\n" . '}';
    }

    public function pStmt_Return(Return_2 $node)
    {
        return 'return' . (null !== $node->expr ? ' ' . $this->p($node->expr) : '');
    }

    public function pScalar_MagicConst_Class(Class_2 $node)
    {
        return $this->getTodo('__CLASS__ was used') . " this.getClass().getName()";
    }

    public function pScalar_MagicConst_Dir(Dir $node)
    {
        return $this->getTodo('__DIR__ was used') . " getClass().getProtectionDomain().getCodeSource().getLocation().getPath()";
    }

    public function pScalar_MagicConst_Line(Line $node)
    {
        return $this->getTodo('__LINE__ was used') . " 1";
    }

    public function pScalar_MagicConst_Method(Method $node)
    {
        return $this->getTodo('__METHOD__ was used') . " 'METHOD'";
    }

    public function pScalar_MagicConst_Function(Function_2 $node)
    {
        return $this->getTodo('__FUNCTION__ was used') . " 'FUNCTION'";
    }

    public function pScalar_MagicConst_File(File $node)
    {
        return $this->getTodo('__FILE__ was used') . " getClass().getProtectionDomain().getCodeSource().getLocation().getPath()";
    }

    /**
     * Empty transpiles to "!" unless the previous symbol is "!"
     * 
     * @param Empty_ $node
     * @return string
     */
    public function pExpr_Empty(Empty_ $node)
    {
        $parent = $node->getAttribute(TreeRelation::PARENT);
        $buffer = ($parent instanceof BooleanNot) ? '' : '!';
        return $buffer . $this->p($node->expr);
    }
    
    public function pExpr_BooleanNot(BooleanNot $node)
    {
        $buffer = '!';
        if ($node->expr instanceof Empty_)
            $buffer = '';
        
        return $this->pPrefixOp('Expr_BooleanNot', $buffer, $node->expr);
    }

    public function pExpr_Eval(Eval_ $node)
    {
        return $this->getTodo('better find a different solution than eval') . ' evaluate(' . $this->p($node->expr) . ')';
    }

    public function pStmt_Goto(Goto_ $node)
    {
        return $this->getTodo("goto is not supported.") . ' ' .$this->getException("goto is not supported.");
    }

    public function pStmt_Global(Global_ $node)
    {
        return  $this->getTodo("global is not supported.") . ' ' .
            $this->getException("global is not supported, maybe try public static class members.");
    }

    public function pStmt_If(If_ $node)
    {
        if ($node->cond instanceof Assign) {
            $cond = '(' . $this->p($node->cond) . ')';
        } else {
            $cond = $this->p($node->cond);
        }
        
        return 'if (' . $cond . ') {'
             . $this->pStmts($node->stmts) . "\n" . '}'
             . $this->pImplode($node->elseifs)
             . (null !== $node->else ? $this->p($node->else) : '');
    }
    
    public function pStmt_ElseIf(ElseIf_ $node)
    {
        return ' else if (' . $this->p($node->cond) . ') {'
            . $this->pStmts($node->stmts) . "\n" . '}';
    }
    
    public function pExpr_List(List_ $node)
    {
        return $this->getTodo("list is not fully supported, check args") . ' def(' . $this->pCommaSeparated($node->items) . ')';
    }
    
    public function pExpr_Cast_Bool(Bool_ $node)
    {
        return $this->getTodo('Check casting precedence') 
            . ' (' . parent::pExpr_Cast_Bool($node).')';
    }
    
    public function pExpr_Cast_Int(Int_ $node)
    {
        return $this->getTodo('Check casting precedence') 
            . ' (' . parent::pExpr_Cast_Int($node).')';
    }
    
    public function pExpr_Cast_String(String_2 $node)
    {
        return $this->getTodo('Check casting precedence') 
            . ' (' . parent::pExpr_Cast_String($node).')';
    }
    
    public function pExpr_Cast_Double(Double $node)
    {
        return $this->getTodo('Check casting precedence') 
            . ' (' . parent::pExpr_Cast_Double($node).')';
    }
    
    public function pExpr_Isset(Isset_ $node)
    {
        if ($node->getAttribute(TreeRelation::PARENT) instanceof If_)
            return $this->pImplode($node->vars, ' && ');
        
        $buffer = [];
        foreach ($node->vars as $var) {
            if ($var instanceof Expr)
                $buffer[] = '(' . $this->p($var) . ')?true:false';
            else
                $buffer[] = $var . '?true:false';
        }
        return implode(' && ',$buffer);
    }

    private function pUseType($type)
    {
        return $type === Use_::TYPE_FUNCTION ? 'static ' :  '';
    }

    protected function pObjectProperty($node)
    {
        if ($node instanceof Expr) {
            return '"$' . $this->p($node) . '"';
        } else {
            return $node;
        }
    }
    
    protected function pModifiers($modifiers) {
        return str_replace("public ", '', parent::pModifiers($modifiers));
    }
    
    private function getState() : State
    {
        if (!isset($this->options['state']))
            throw new RuntimeException('No state option present.');
        
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

    private function throwError(Node $node)
    {
        throw new Error("ArrayItem conversion error on " . serialize($node) . "in " . $this->getState()->getFilename(), $node->getAttributes());
    }

}
