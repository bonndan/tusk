<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Tusk\NodeVisitor\PHP2Groovy\Implode_;
use Tusk\NodeVisitor\PHP2Groovy\Explode_;

/**
 * Replaces native PHP with native Groovy.
 * 
 * 
 *
 */
class PHP2Groovy extends NodeVisitorAbstract implements InfluencingVisitor
{
    /**
     * @var PHP2Groovy\NodeExchanger 
     */
    private $visitors = [];
    
    public function __construct()
    {
        $this->visitors[] = new Implode_();
        $this->visitors[] = new Explode_();
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
