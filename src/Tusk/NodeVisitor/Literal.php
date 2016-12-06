<?php

namespace Tusk\NodeVisitor;

use PhpParser\NodeVisitorAbstract;

/**
 * Base class for visitors introducing replacements.
 * 
 * 
 */
abstract class Literal extends NodeVisitorAbstract implements InfluencingVisitor
{
     const REPLACEMENT = 'replacement';
}
