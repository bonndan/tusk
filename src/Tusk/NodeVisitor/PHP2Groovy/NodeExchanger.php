<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

/**
 * Simple version of NodeVisitor. Exchanges nodes if something needs to be replaced.
 * 
 *
 */
interface NodeExchanger
{
    /**
     * Returns a node if the old one needs to be replaced.
     * 
     * @param \PhpParser\Node $node
     * @return \PhpParser\Node|null
     */
    public function handleNode(\PhpParser\Node $node);
}
