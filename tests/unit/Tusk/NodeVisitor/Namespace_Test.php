<?php
/**
 * Description of BuiltInExceptionTest
 *
 * 
 */
class Namespace_Test extends PHPUnit\Framework\TestCase
{

    private $state;
    
    /**
     * system under test
     * @var \Tusk\NodeVisitor\BuiltInException
     */
    private $visitor;

    public function setUp()
    {
        parent::setUp();
        $this->state = new Tusk\State('test');
        $this->visitor = new Tusk\NodeVisitor\Namespace_($this->state);
    }
    
    public function testAddsNamespaceNode()
    {
        $this->state->setPackage("a.b.c");
        $actual = $this->visitor->afterTraverse([]);
        $this->assertNotEmpty($actual);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Namespace_::class, $actual[0]);
    }
    
    public function testLeavesAsIs()
    {
        $actual = $this->visitor->afterTraverse([]);
        $this->assertEmpty($actual);
    }
    
    public function testDoesNotChangeNamespace()
    {
        $this->state->setPackage("a.b.c");
        $nodes = [new \PhpParser\Node\Stmt\Namespace_(new PhpParser\Node\Name('test'))];
        $this->visitor->enterNode($nodes[0]);
        $actual = $this->visitor->afterTraverse($nodes);
        $this->assertNull($actual);
    }
    
}
