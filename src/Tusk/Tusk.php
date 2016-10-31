<?php

namespace Tusk;

use \PhpParser\Error;
use \PhpParser\ParserFactory;

/**
 * Description of Tusk
 *
 * 
 */
class Tusk
{

    public function run()
    {
        $code = file_get_contents(dirname(__DIR__, 2) . '/test/examples/TestClass.php');
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
