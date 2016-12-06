<?php

namespace Tusk\NodeVisitor;

/**
 * Modifies access to 
 * 
 *  $GLOBALS
 *  $_GET
 *  $_POST
 *  $_FILES
 *  $_COOKIE
 *  $_SESSION
 *  $_REQUEST
 *  $_ENV
 * 
 * by replacing it with calls to servlet request etc.
 * 
 * @todo distinguish between url and body param (mostly GET, POST)
 */
class GlobalsExchanger extends Literal
{
    private $mapping = [
        'GLOBALS' => '/* TODO use a static Globals map */  Globals.get("XXX")',
        '_GET' => 'URLDecoder.decode(request.getQueryString(), "UTF-8")',
        '_POST' => 'request.getParameterMap()',
        '_FILES' => '/* TODO various solutions */ request.getParts()',
        '_COOKIE' => 'request.getCookies()',
        '_SESSION' => '((HttpServletRequest) request).getSession()',
        '_REQUEST' => 'request.getParameterMap()',
        '_ENV' => 'System.getenv()',
    ];

    public function enterNode(\PhpParser\Node $node)
    {
        if (!$node instanceof \PhpParser\Node\Expr\Variable)
            return;
        
        if (!is_string($node->name))
            return;
        
        if (array_key_exists($node->name, $this->mapping)) {
            $node->setAttribute(self::REPLACEMENT, $this->mapping[$node->name]);
        }
    }

}
