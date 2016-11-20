<?php
/**
 * Description of BuiltInExceptionTest
 *
 * 
 */
class BuiltInExceptionTest extends PHPUnit\Framework\TestCase
{

    private $state;
    
    /**
     * @var \PhpParser\Node\Name
     */
    private $nameNode;
    
    /**
     * system under test
     * @var \Tusk\NodeVisitor\BuiltInException
     */
    private $visitor;

    public function setUp()
    {
        parent::setUp();
        $this->state = new Tusk\State('test');
        $this->visitor = new \Tusk\NodeVisitor\BuiltInException($this->state);
    }
    
    /**
     * Must not change
     */
    public function testBuiltInWithoutNamespace()
    {
        $this->nameNode = new \PhpParser\Node\Name(['InvalidArgumentException']);
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("IllegalArgumentException");
    }
    
    /**
     * Must not change
     */
    public function testBuiltInWithNamespace()
    {
        $this->nameNode = new \PhpParser\Node\Name(['InvalidArgumentException']);
        $this->state->setNamespace("ABC");
        
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("InvalidArgumentException");
    }
    
    /**
     * must change
     */
    public function testBuiltInWithNamespaceAndPrefix()
    {
        $this->nameNode = new \PhpParser\Node\Name(['\\InvalidArgumentException']);
        $this->state->setNamespace("ABC");
        
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("IllegalArgumentException");
    }
    
    /**
     * builtin used: must change
     */
    public function testBuiltInUsedWithoutPrefix()
    {
        $this->nameNode = new \PhpParser\Node\Name(['InvalidArgumentException']);
        $this->state->addUse("InvalidArgumentException");
        
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("IllegalArgumentException");
    }
    
    /**
     * Must change
     */
    public function testBuiltInUsedOtherNoNamespace()
    {
        $this->nameNode = new \PhpParser\Node\Name(['InvalidArgumentException']);
        $this->state->addUse("A\InvalidArgumentException");
        
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("IllegalArgumentException");
    }
    
    public function testBuiltInUsedOtherWithNamespace()
    {
        $this->nameNode = new \PhpParser\Node\Name(['InvalidArgumentException']);
        $this->state->addUse("A\InvalidArgumentException");
        $this->state->setNamespace("ABC");
        
        $this->visitor->enterNode($this->nameNode);
        $this->assertIs("InvalidArgumentException");
    }
    
    public function assertIs(string $exName)
    {
        $this->assertEquals($exName, $this->nameNode->parts[0]);
    }
}
