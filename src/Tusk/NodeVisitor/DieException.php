<?php

namespace Tusk\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;

/**
 * Adds an import of GroovyException if "die" is used.
 * 
 * 
 *
 */
class DieException extends StatefulVisitor
{

    public function enterNode(Node $node)
    {
        if (!$node instanceof Exit_)
            return;

        $kind = $node->getAttribute('kind', Exit_::KIND_DIE);
        if ($kind === Exit_::KIND_DIE)
            $this->state->addImport("org.codehaus.groovy.GroovyException");
    }

}
