<?php

require_once __DIR__ .'/TestCase.php';

/**
 * Tests exchange of native php with groovy.
 *
 */
class PHP2GroovyTest extends TestCase
{
    public function testExchangeImplode()
    {
        $code = "\$a = ['a', 'b'];
    \$b = implode(',', \$a);
";

        $groovy = $this->parse($code);
        $this->assertContains("a.join(',')", $groovy);
        $this->assertNotContains("implode", $groovy);
    }
    
    public function testExchangeExplode()
    {
        $code = "\$a = explode(',', 'a,b');";

        $groovy = $this->parse($code);
        $this->assertContains("a = 'a,b'.split(',')", $groovy);
        $this->assertNotContains("explode", $groovy);
    }
    
    public function testExchangeExplode2()
    {
        $code = "\$a = explode(',', someFunction());";

        $groovy = $this->parse($code);
        $this->assertContains("a = someFunction().split(',')", $groovy);
        $this->assertNotContains("explode", $groovy);
    }
    
    public function testExchangeInArray1()
    {
        $code = "\$a = in_array(\$b, someFunction());";

        $groovy = $this->parse($code);
        $this->assertContains("a = someFunction().contains(b)", $groovy);
        $this->assertNotContains("in_array", $groovy);
    }
    
      
    
    public function testInvalidArgumentExceptionWithoutNamespace()
    {
        $code = "
throw new InvalidArgumentException('test');
";
        $groovy = $this->parse($code);
        $this->assertContains("IllegalArgumentException", $groovy);
        $this->assertNotContains("InvalidArgumentException", $groovy);
    }
    
    public function testInvalidArgumentExceptionInClass()
    {
        $code = "
namespace A;

class B {
    function b()
    {
        throw new \\InvalidArgumentException('test');
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("IllegalArgumentException", $groovy);
        $this->assertNotContains("InvalidArgumentException", $groovy);
    }
    
    public function testSessionAccessAddsImport()
    {
        $code = "class A {
    
    function a()
    {
        return \$_SESSION['x'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("import javax.servlet.http.HttpServletRequest", $groovy);
    }
    
    public function testReflectionClass()
    {
        $code = "
 \$a = new \ReflectionClass('X');
            ";
        $groovy = $this->parse($code);
        $this->assertContains("X.metaClass", $groovy);
    }
    
    public function testInstanceOfStdClass()
    {
        $code = "
 \$a = \$x instanceof stdclass;
            ";
        $groovy = $this->parse($code);
        $this->assertContains("instanceof Object", $groovy);
        $this->assertNotContains("stdclass", $groovy);
    }
    
    public function testStrCase()
    {
        $code = "
 \$a = strtolower('A');
 \$b = strtoupper('b');
";
        
        $groovy = $this->parse($code);
        $this->assertContains("a = 'A'.toLowerCase()", $groovy);
        $this->assertContains("b = 'b'.toUpperCase()", $groovy);
        $this->assertNotContains("strtolower", $groovy);
        $this->assertNotContains("strtoupper", $groovy);
    }
}
