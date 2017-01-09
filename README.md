# Tusk

[![Build Status](https://travis-ci.org/bonndan/tusk.svg?branch=master)](https://travis-ci.org/bonndan/tusk)
[![Dependency Status](https://gemnasium.com/badges/github.com/bonndan/tusk.svg)](https://gemnasium.com/github.com/bonndan/tusk)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg)](https://php.net/)

Tusk is an experimental transpiler to convert PHP sources into http://groovy-lang.org/. 
In order to accomplish this it creates an AST using https://github.com/nikic/PHP-Parser and then attempts to write
the nodes in Groovy syntax.

## Goal

Tusk is meant as support tool to port PHP projects to Groovy/Java with low efforts. It does not
aim at full-auto magic conversion, but aims to create mostly error-free Groovy sources
enriched with hints. Then with a decent IDE missing things can be quickly resolved.

## Features

PHP source:
![screenshot](https://raw.githubusercontent.com/bonndan/tusk/master/doc/user_php.png "PHP source")

Groovy outuput
![screenshot](https://raw.githubusercontent.com/bonndan/tusk/master/doc/user_groovy.png "Groovy source")

* converts php expressions into valid Groovy
* handles incompatibilites as far as possible (e.g. "break 2")
* evaluates type information gathered by PHP-Parser and tries to find equivalents
* uses DocBlock type information where available
* convert some builtin php functions with Groovy equivalents (e.g. isset)
* add automatic custom imports
* add on demand imports of non-resolvable classes via mapping

## Configuration

```php

require './vendor/autoload.php';
ini_set('xdebug.max_nesting_level', 3000);

$t = new \Tusk\Tusk(Tusk\Configuration::create(
    [
        'source' => dirname(__DIR__) . '/mylegacyproject',
        'target' => dirname(__DIR__) . '/myshinynewgroovyproject',
        'namespaces' => [
            'src' => '__AUTO__',
            
            //folder actions go into package Shiny.Actions
            'actions' => 'Shiny.Actions',
            //include files go into package Shiny.Common
            'include' => 'Shiny.Common',
        ],
        
        //these folders are copied into the resources dir
        'resources' => [
            'templates' => 'templates',
            'languages' => 'languages',
        ],
        
        //the docs folder is copied into a new dir documentation
        'other' => [
            'docs' => 'documentation'
        ],
        
        //if these class names appear, import statements are added
        'onDemandImport' => [
            'Controller' => 'Shiny.Controller',
            'DatabaseInterface' => 'Shiny.DatabaseInterface',
            'Event' => 'Shiny.Event',
            'Base' => 'Shiny.Base',
            'Pagination' => 'Shiny.Pagination',
        ],
        
        //
        'replaceNames' => [
            //use function > import static
            'between' => 'Shiny.functions.between', 
            'valNum' => 'Shiny.functions.valNum',
            
            //simple name replacement, here a constant
            'DIR_SEPERATOR' => "'/'",
        ]
    ]
));

$t->run();
```

## Challenges

* arrays. It's hard to find out whether a PHP array can be translated into a Collection, Array or Map or won't work.
* PHP built-in functions need to be rewritten in Java or Groovy or replaced with equivalents.
* garbage like "goto"
* fancy stuff like yield, destructors, magic methods and constants
* casting precedence of objects (non-scalar) not regarded yet
* use eachwithindex if loop does not contain: break, continue, return
* prevent useless casting: if ((int) id > 0) where id is already int
* simplify boolean comparison: "false==" or "true=="
