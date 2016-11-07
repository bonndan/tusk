<?php

require './vendor/autoload.php';
ini_set('xdebug.max_nesting_level', 3000);

$t = new \Tusk\Tusk();

if (!isset($argv[1]))
    $path = __DIR__ . '/tests/examples/TestClass.php';
else
    $path = $argv[1];
$t->run($path);