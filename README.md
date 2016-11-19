# Tusk

Tusk is an experimental transpiler to convert PHP sources into http://groovy-lang.org/. 
In order to accomplish this it creates an AST using https://github.com/nikic/PHP-Parser and then attempts to write
the nodes in Groovy syntax.

## Features

* evaluates type information gathered by PHP-Parser and tries to find equivalents
* uses DocBlock type information where available

## Challenges

* arrays. It's hard to find out whether a PHP array can be translated into a List or Map or won't work.
* PHP built-in functions need to be rewritten in Java or Groovy or replaced with equivalents.
* garbage like "goto"
* fancy stuff like yield, destructors, magic methods and constants


## Errors / TODO

* use function $name --> import static $name
* iterate maps using for + set vars, because eachWithIndex does not allow break/continue
* iterate lists using for, index possible?
* add newline before multiline close when inner string ends on double quote
* trait methods must be public :(
* assign-in-if within parentheses
* InvalidArgumentException --> IllegalArgumentException
* LogicException --> RuntimeException