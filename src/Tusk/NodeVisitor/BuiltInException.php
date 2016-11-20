<?php

namespace Tusk\NodeVisitor;

/**
 * Turns built in PHP exception into Groovy equivalents.
 * 
 * E.g. InvalidArgumentException into IllegalArgumentException
 */
class BuiltInException extends StatefulVisitor
{

    private static $conversion = [
        'BadFunctionCallException' => 'RuntimeException',
        'BadMethodCallException' => 'RuntimeException',
        'DomainException' => 'RuntimeException',
        'InvalidArgumentException' => 'IllegalArgumentException',
        'LengthException' => 'RuntimeException',
        'LogicException' => 'RuntimeException',
        'OutOfBoundsException' => 'RuntimeException',
        'OutOfRangeException' => 'RuntimeException',
        'OverflowException' => 'RuntimeException',
        'RangeException' => 'RuntimeException',
        'RuntimeException' => 'RuntimeException',
        'UnderflowException' => 'RuntimeException',
        'UnexpectedValueException' => 'IllegalArgumentException',
    ];

    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Name)
            return;

        foreach ($node->parts as $i => $part) {
            foreach (self::$conversion as $from => $to) {
                if (strpos($part, $from) !== false && $this->isBuiltin($part))
                    $node->parts[$i] = $to;
            }
        }
    }

    private function isBuiltin(string $exceptionName): bool
    {

        //hasSeparatorsWithin ?
        if (strpos($exceptionName, '\\', 1) !== false)
            return false;

        if ($exceptionName[0] == '\\') {
            return true;
        }

        //check use/import
        if ($this->state->isUsed($exceptionName)) {
            return true;
        }

        if (empty($this->state->getPackage())) {
            return true;
        }



        return false;
    }

}
