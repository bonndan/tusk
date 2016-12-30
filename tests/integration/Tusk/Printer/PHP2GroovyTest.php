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
}
