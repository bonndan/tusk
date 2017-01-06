<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Tusk\NodeVisitor\PHP2Groovy\Explode_;
use Tusk\NodeVisitor\PHP2Groovy\Implode_;
use Tusk\NodeVisitor\PHP2Groovy\InArray;
use Tusk\NodeVisitor\PHP2Groovy\NodeExchanger;
use Tusk\NodeVisitor\PHP2Groovy\StrCase;

/**
 * Replaces native PHP with native Groovy.
 * 
 * 
 *
 */
class PHP2Groovy extends NodeVisitorAbstract implements InfluencingVisitor
{
    /**
     * @var NodeExchanger 
     */
    private $visitors = [];
    
    public function __construct()
    {
        $this->visitors[] = new Implode_();
        $this->visitors[] = new Explode_();
        $this->visitors[] = new InArray();
        $this->visitors[] = new StrCase();
    }
    
    public function enterNode(Node $node)
    {
        foreach ($this->visitors as $visitor) {
            $res = $visitor->handleNode($node);
            if ($res) {
                return $res;
            }
        }
    }
}
