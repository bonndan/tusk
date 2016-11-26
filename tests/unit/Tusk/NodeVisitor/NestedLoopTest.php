<?php

/**
 * Test of NestedLoop Visitor
 *
 * 
 */
class NestedLoopTest extends PHPUnit\Framework\TestCase
{
    /**
     * system under test
     * @var Tusk\NodeVisitor\NestedLoop
     */
    private $visitor;

    public function setUp()
    {
        parent::setUp();
        $this->visitor = new Tusk\NodeVisitor\NestedLoop();
    }
    
    public function testDepth()
    {
        $loop1 = new PhpParser\Node\Stmt\For_();
        $loop2 = new PhpParser\Node\Stmt\For_();
        
        $this->visitor->enterNode($loop1);
        $this->assertEquals(1, $loop1->getAttribute(Tusk\NodeVisitor\NestedLoop::DEPTH));
        
        $this->visitor->enterNode($loop2);
        $this->assertEquals(2, $loop2->getAttribute(Tusk\NodeVisitor\NestedLoop::DEPTH));
    }
    
    public function testOuterLoop()
    {
        $loop1 = new PhpParser\Node\Stmt\For_();
        $loop2 = new PhpParser\Node\Stmt\For_();
        
        $this->visitor->enterNode($loop1);
        $this->assertEquals(null, $loop1->getAttribute(Tusk\NodeVisitor\NestedLoop::OUTER_LOOP));
        
        $this->visitor->enterNode($loop2);
        $this->assertEquals($loop1, $loop2->getAttribute(Tusk\NodeVisitor\NestedLoop::OUTER_LOOP));
        
        $this->visitor->leaveNode($loop2);
        $this->visitor->leaveNode($loop1);
        
        //same assertions after leaving
        $this->assertEquals(null, $loop1->getAttribute(Tusk\NodeVisitor\NestedLoop::OUTER_LOOP));
        $this->assertEquals($loop1, $loop2->getAttribute(Tusk\NodeVisitor\NestedLoop::OUTER_LOOP));
    }
    
    public function testBreakWithTargetLoop()
    {
        $loop1 = new PhpParser\Node\Stmt\For_();
        $loop2 = new PhpParser\Node\Stmt\For_();
        $break = new PhpParser\Node\Stmt\Break_(new \PhpParser\Node\Scalar\LNumber(2));
        $loop2->stmts = [$break];
        
        $this->visitor->enterNode($loop1);
        $this->assertNull($break->getAttribute(Tusk\NodeVisitor\NestedLoop::TARGET_LOOP));
        
        $this->visitor->enterNode($loop2);
        $this->assertEquals('loop1', $break->getAttribute(Tusk\NodeVisitor\NestedLoop::TARGET_LOOP));
    }
}
