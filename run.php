<?php

require './vendor/autoload.php';
ini_set('xdebug.max_nesting_level', 3000);

$t = new \Tusk\Tusk();
$t->run();