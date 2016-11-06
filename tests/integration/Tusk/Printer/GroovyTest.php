<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the tusk printer.
 *
 */
class GroovyTest extends TestCase
{

    /**
     * system under test
     * @var Tusk\Printer\Groovy
     */
    private $printer;
    private $parser;

    protected function setUp()
    {
        $this->printer = new Tusk\Printer\Groovy();
        $this->parser = (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7);
    }

    public function testClassConstString()
    {
        $code = "class abc {
    const ABC_DEF = '123';
}";

        $this->assertContains("public final String ABC_DEF = '123'", $this->parse($code));
    }

    public function testClassConstInteger()
    {
        $code = "class abc {
    const ABC_DEF = 123;
}";

        $this->assertContains("public final Integer ABC_DEF = 123", $this->parse($code));
    }

    public function testClassProperty()
    {
        $code = "class abc {
    private \$a;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyNull()
    {
        $code = "class abc {
    private \$a = null;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyTypeFromComment()
    {
        $code = "class abc {
    /**
     * @var string
     */
    private \$a = null;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private String a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyTypeFromCommentScalarArray()
    {
        $code = "class abc {
    /**
     * @var string[]
     */
    private \$a;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private String[] a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyTypeFromCommentObjectArray()
    {
        $code = "class abc {
    /**
     * @var TestClass[]
     */
    private \$a;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private TestClass[] a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyTypeFromNamespacedClass()
    {
        $code = "class abc {
    /**
     * @var Test\Class
     */
    private \$a;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private Test.Class a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyInteger()
    {
        $code = "class abc {
    private \$a = 123;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private Integer a = 123", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyString()
    {
        $code = "class abc {
    protected \$a = '123';
}";

        $groovy = $this->parse($code);
        $this->assertContains("protected String a = '123'", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testMethod()
    {
        $code = "class abc {
    protected function abc(){}
}";

        $groovy = $this->parse($code);
        $this->assertContains('protected def abc()', $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testConstructorRenamed()
    {
        $code = "class abc {
    function __construct(){}
}";

        $groovy = $this->parse($code);
        $this->assertContains('abc()', $groovy);
        $this->assertNotContains('__construct', $groovy);
    }
    
    public function testNamespaceToPackage()
    {
        $code = "namespace Test\A;
class abc {
    function __construct(){}
}";

        $groovy = $this->parse($code);
        $this->assertContains('package Test.A', $groovy);
        $this->assertNotContains('namespace Test\A', $groovy);
    }
    
    public function testForLoop()
    {
        $code = '
for ($i=0;$i<10;$i++) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('for (int i = 0; i < 10; i++)', $groovy);
        $this->assertNotContains('for ($i=0;$i<10;$i++)', $groovy);
    }
    
    public function testForEachLoop()
    {
        $code = '
            $arr = [];
foreach ($arr as $value) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('for (value in arr) {', $groovy);
        $this->assertNotContains('for ($i=0;$i<10;$i++)', $groovy);
    }
    
    public function testForEachLoopWithKey()
    {
        $code = '
            $arr = [];
foreach ($arr as $key => $value) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('arr.eachWithIndex { value, key ->', $groovy);
        $this->assertNotContains('for ($i=0;$i<10;$i++)', $groovy);
    }
    
    public function testStringConcat()
    {
        $code = '
            $test = "one" . "two";
';

        $groovy = $this->parse($code);
        $this->assertContains('test = "one" + "two"', $groovy);
    }
    
    public function testVarConcat()
    {
        $code = '
            $test = "one";
            $test .= "two";
';

        $groovy = $this->parse($code);
        $this->assertContains('test += "two"', $groovy);
    }
    
    public function testTraits()
    {
        $code = "namespace Test\A;
class A implements \ArrayAccess {
    use BTrait;
    use CTrait;
}";

        $groovy = $this->parse($code);
        $this->assertContains('class A implements ArrayAccess, BTrait, CTrait', $groovy);
        $this->assertNotContains('use BTrait;', $groovy);
        $this->assertNotContains('use CTrait;', $groovy);
    }
    
    public function testTraitsConflictResolution()
    {
        $code = "class Talker {
    use A, B {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B;
    }
}";

        $groovy = $this->parse($code);
        $this->assertContains('class Talker implements A, B', $groovy);
        $this->assertNotContains('use A, B {
        B::smallTalk insteadof A;
        A::bigTalk insteadof B;
    }', $groovy);
        
        $this->assertContains('smallTalk(){B.super.smallTalk()}', $this->normalizeInvisibleChars($groovy));
        $this->assertContains('bigTalk(){A.super.bigTalk()}', $this->normalizeInvisibleChars($groovy));
        $this->assertNotContains('function', $groovy);
    }

    public function testParentCall()
    {
        $code = "
class A {
    
    protected function a() {
        return parent::a();
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('super.a()', $groovy);
        $this->assertNotContains('parent::a()', $groovy);
    }
    
    public function testStaticClassVar()
    {
        $code = "
class A {
    
    protected function a() {
        return B::\$b;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('B.b', $groovy);
        $this->assertNotContains('B::$b', $groovy);
    }
    
    public function testStaticClassMethod()
    {
        $code = "
class A {
    
    protected function a() {
        return B::b();
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('B.b()', $groovy);
        $this->assertNotContains('B::b()', $groovy);
    }
    
    /**
     * @param string $code without leading <?php 
     * @return string
     */
    private function parse(string $code): string
    {
        $stmts = $this->parser->parse("<?php " . $code);
        return $this->printer->prettyPrint($stmts);
    }

    private function normalizeInvisibleChars(string $str) : string
    {
        return  str_replace(PHP_EOL, "",  str_replace("\t", "", str_replace(" ", "", $str)));
    }
}
