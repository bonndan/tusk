<?php

namespace Tusk;

use \PhpParser\Error;
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
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $printer = new Printer\Groovy();
        
        try {
            $stmts = $parser->parse($code);
            echo $printer->prettyPrint($stmts);
        }
        catch (Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }

}
