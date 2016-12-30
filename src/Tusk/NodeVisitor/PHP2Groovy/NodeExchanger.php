<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

/**
 * Description of NodeExchanger
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
