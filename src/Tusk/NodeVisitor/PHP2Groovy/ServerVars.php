<?php

namespace Tusk\NodeVisitor\PHP2Groovy;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use Tusk\NodeVisitor\Literal;
use Tusk\NodeVisitor\TreeRelation;

/**
 * Attempts to translate access to $_SERVER vars.
 *
 * 
 */
class ServerVars extends Literal
{

    private static $serverVars = [
        'PHP_SELF' => 'request.getRequestURL()',
        'argv' => '/* TODO no global access to argv */ args',
        'argc' => '/* TODO no global access to argc */ args',
        'GATEWAY_INTERFACE' => '/* TODO GATEWAY_INTERFACE not supported */ ""',
        'SERVER_ADDR' => 'request.getLocalAddr()',
        'SERVER_NAME' => 'request.getServerName()',
        'SERVER_SOFTWARE' => 'request.getServletContext().getServerInfo()',
        'SERVER_PROTOCOL' => 'request.getProtocol()',
        'REQUEST_METHOD' => 'request.getMethod()',
        'REQUEST_TIME' => '/* TODO request start timestamp, this is wrong: */ System.currentTimeMillis() / 1000L',
        'REQUEST_TIME_FLOAT' => '/* TODO request start timestamp, this is wrong: */ System.currentTimeMillis()',
        'QUERY_STRING' => 'request.getQueryString()',
        'DOCUMENT_ROOT' => 'request.getServletContext().getRealPath("/")',
        'HTTP_ACCEPT' => 'request.getHeader("Accept")',
        'HTTP_ACCEPT_CHARSET' => 'request.getHeader("Accept-Charset")',
        'HTTP_ACCEPT_ENCODING' => 'request.getHeader("Accept-Encoding")',
        'HTTP_ACCEPT_LANGUAGE' => 'request.getHeader("Accept-Language")',
        'HTTP_CONNECTION' => 'request.getHeader("Connection")',
        'HTTP_HOST' => 'request.getHeader("Host")',
        'HTTP_REFERER' => 'request.getHeader("Referer")',
        'HTTP_USER_AGENT' => 'request.getHeader("User-Agent")',
        'HTTPS' => 'request.getScheme() == "https" ? "on" : ""',
        'REMOTE_ADDR' => 'request.getHeader("Remote_Addr")',
        'REMOTE_HOST' => 'request.getRemoteHost()',
        'REMOTE_PORT' => 'request.getRemotePort()',
        'REMOTE_USER' => 'request.getRemoteUser()',
        'REDIRECT_REMOTE_USER' => '/* TODO REDIRECT_REMOTE_USER not supported */ ""',
        'SCRIPT_FILENAME' => '/* TODO SCRIPT_FILENAME not supported */ ""',
        'SERVER_ADMIN' => '/* TODO SERVER_ADMIN not supported */ ""',
        'SERVER_PORT' => 'request.getLocalPort()',
        'SERVER_SIGNATURE' => '/* TODO SERVER_SIGNATURE */ ""',
        'PATH_TRANSLATED' => 'request.getPathTranslated()',
        'SCRIPT_NAME' => 'request.getServletPath()',
        'REQUEST_URI' => 'request.getRequestURI().substring(request.getContextPath().length())',
        'PHP_AUTH_DIGEST' => '/* TODO PHP_AUTH_DIGEST */ ""',
        'PHP_AUTH_USER' => '/* TODO PHP_AUTH_USER */ ""',
        'PHP_AUTH_PW' => '/* TODO PHP_AUTH_PW */ ""',
        'AUTH_TYPE' => 'request.getAuthType()',
        'PATH_INFO' => 'request.getPathInfo()',
        'ORIG_PATH_INFO' => '/* TODO ORIG_PATH_INFO */ ""'
    ];

    public function enterNode(Node $node)
    {
        if (!$node instanceof Variable)
            return;

        if (!is_string($node->name) || $node->name != '_SERVER')
            return;
        
        $parent = $node->getAttribute(TreeRelation::PARENT);
        if (!$parent instanceof ArrayDimFetch)
            return;
        
        if (!isset($parent->dim->value))
            return;
        
        if (array_key_exists($parent->dim->value, self::$serverVars)) {
            $node->setAttribute(self::REPLACEMENT, self::$serverVars[$parent->dim->value]);
        }
    }

}
