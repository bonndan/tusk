<?php

namespace Tusk\NodeVisitor;

/**
 * Replaces reserved words.
 * 
 * E.g. in PHP a variable name "short" is allowed.
 * 
 * @todo might need more replacements
 */
class ReservedWords extends \PhpParser\NodeVisitorAbstract implements InfluencingVisitor
{

    public static $reservedWords = [
        "abstract",
        "as",
        "continue",
        "for",
        "new",
        "switch",
        "assert",
        "default",
        "if",
        "package",
        "synchronized",
        "boolean",
        "do",
        "goto",
        "private",
        "this",
        "break",
        "double",
        "implements",
        "protected",
        "throw",
        "byte",
        "else",
        "import",
        "in",
        "public",
        "throws",
        "case",
        "enum",
        "instanceof",
        "return",
        "transient",
        "catch",
        "extends",
        "int",
        "short",
        "try",
        "char",
        "final",
        "interface",
        "static",
        "void",
        "class",
        "finally",
        "long",
        "strictfp",
        "volatile",
        "const",
        "float",
        "native",
        "super",
        "while",
    ];

    public function enterNode(\PhpParser\Node $node)
    {
        if ($node instanceof \PhpParser\Node\Expr\Variable) {
            foreach (self::$reservedWords as $word) {
                if ($word == 'this')
                    continue;
                if (strtolower($node->name) == $word) {
                    $node->name = '_' . $node->name;
                }
            }
        }
        
        if ($node instanceof \PhpParser\Node\Param) {
            foreach (self::$reservedWords as $word) {
                if (strtolower($node->name) == $word) {
                    $node->name = '_' . $node->name;
                }
            }
        }
       
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            foreach (self::$reservedWords as $word) {
                if (strtolower($node->name) == $word) {
                    $node->name = '_' . $node->name . ' /* TODO class was renamed */ ';
                }
            }
        }
    }

}
