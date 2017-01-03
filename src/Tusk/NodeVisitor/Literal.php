<?php

namespace Tusk\NodeVisitor;

/**
 * Base class for visitors introducing replacements.
 * 
 * 
 */
abstract class Literal extends StatefulVisitor implements InfluencingVisitor
{
     const REPLACEMENT = 'replacement';
}
