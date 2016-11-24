<?php

namespace Tusk\NodeVisitor;

/**
 * Resolves node to node relations in the AST, similar to DOM.
 * 
 * Copied from https://github.com/felixfbecker/php-language-server/
 * 
 * @author Felix Becker
 */
class TreeRelation extends StatefulVisitor
{
    /**
     * describes the node that has been visited before
     */
    const PARENT = 'parentNode';
    const PREVIOUS = 'previousSibling';
    const NEXT = 'nextSibling';
    
    /**
     * @var Node[]
     */
    private $stack = [];

    /**
     * @var Node
     */
    private $previous;

    public function enterNode(\PhpParser\Node $node)
    {
        $node->setAttribute('ownerDocument', $this->state->getFilename());
        if (!empty($this->stack)) {
            $node->setAttribute(self::PARENT, end($this->stack));
        }
        
        if (isset($this->previous) && $this->previous->getAttribute(self::PARENT) === $node->getAttribute(self::PARENT)) {
            $node->setAttribute(self::PREVIOUS, $this->previous);
            $this->previous->setAttribute(self::NEXT, $node);
            
        }
        /*var_dump(
            get_class($node),
            get_class($node->getAttribute(self::PARENT)),
            get_class($node->getAttribute(self::PREVIOUS)),
            '---');*/
        $this->stack[] = $node;
    }

    public function leaveNode(\PhpParser\Node $node)
    {
        $this->previous = $node;
        array_pop($this->stack);;
    }
}
