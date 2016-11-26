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
enriched with hints.

## Features

* converts php expressions into valid Groovy
* handles incompatibilites as far as possible (e.g. "break 2")
* evaluates type information gathered by PHP-Parser and tries to find equivalents
* uses DocBlock type information where available
* convert some builtin php functions with Groovy equivalents (e.g. isset)

## Challenges

* arrays. It's hard to find out whether a PHP array can be translated into a Collection, Array or Map or won't work.
* PHP built-in functions need to be rewritten in Java or Groovy or replaced with equivalents.
* garbage like "goto"
* fancy stuff like yield, destructors, magic methods and constants


## Errors / TODO

* casting precedence of objects (non-scalar) not regarded yet
* use eachwithindex if loop does not contain: break, continue, return
* prevent useless casting: if ((int) id > 0) where id is already int
* simplyfy boolean comparison: "false==" or "true=="
