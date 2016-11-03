<?php

namespace Test;

/**
 * Description of TestClass
 *
 * 
 */
class TestClass
{
    const ABC = "abc";
    const DEF = 1;

    /**
     * @var string 
     */
    private $arg;

    private $singleQuoted = 'abc';
    
    /**
     * 
     * @param string $arg
     */
    public function __construct($arg)
    {
        $this->arg = $arg;
    }
}
