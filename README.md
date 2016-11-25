# Tusk

Tusk is an experimental transpiler to convert PHP sources into http://groovy-lang.org/. 
In order to accomplish this it creates an AST using https://github.com/nikic/PHP-Parser and then attempts to write
the nodes in Groovy syntax.

## Features

* converts php expressions into valid Groovy
* evaluates type information gathered by PHP-Parser and tries to find equivalents
* uses DocBlock type information where available

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
* "break 2" shows TODO that break-with-labels must be used