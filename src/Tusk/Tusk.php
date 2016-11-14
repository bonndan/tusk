<?php

namespace Tusk;

use \PhpParser\ParserFactory;

/**
 * Tusk entry point.
 *
 * 
 */
class Tusk
{

    /**
     * Converts a php source file into groovy.
     * 
     * @param string $file
     */
    public function run(string $file)
    {
        $code = file_get_contents($file);
        echo $this->toGroovy($this->getStatements($code));
    }

    public function toGroovy(array $statements)
    {
        $printer = new Printer\Groovy();
        return $printer->prettyPrint($statements);
    }

    public function getStatements(string $code): array
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $traverser = new \PhpParser\NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\Destruct());
        $traverser->addVisitor(new NodeVisitor\MagicCall());

        $stmts = $parser->parse($code);
        $stmts = $traverser->traverse($stmts);
        return $stmts;
    }

}
