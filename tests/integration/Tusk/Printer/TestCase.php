<?php

/**
 * Tests the tusk printer.
 *
 */
class TestCase extends PHPUnit\Framework\TestCase
{
    /**
     * @var Tusk\Tusk
     */
    protected $tusk;
    
    /**
     * @var \Tusk\Configuration
     */
    protected $config;

    protected function setUp()
    {
        $this->config = new \Tusk\Configuration();
        $this->tusk = new Tusk\Tusk($this->config);
    }
    
    
    /**
     * @param string $code without leading <?php 
     * @return string
     */
    protected function parse(string $code): string
    {
        $state = new Tusk\State('test');
        return $this->tusk->toGroovy(
            $this->tusk->getStatements("<?php " . $code, $state),
            $state
        );
    }

    protected function normalizeInvisibleChars(string $str) : string
    {
        return  str_replace(PHP_EOL, "",  str_replace("\t", "", str_replace(" ", "", $str)));
    }
}
