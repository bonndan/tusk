<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the tusk printer.
 *
 */
class GroovyTest extends TestCase
{
    /**
     * @var Tusk\Tusk
     */
    private $tusk;

    protected function setUp()
    {
        $this->tusk = new Tusk\Tusk();
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
    
    public function testChaining()
    {
        $code = "
class A {
    
    private \$something;
        
    protected function a() {
        return \$this->something->getOtherThing()->toString();
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('something.getOtherThing().toString()', $groovy);
        $this->assertNotContains('$this->something->getOtherThing()->toString()', $groovy);
    }
    
    public function testDynamicCall()
    {
        $code = "
class A {
    
    protected function a(\$method) {
        return \$this->\$method();
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('this."$method"()', $groovy);
        $this->assertNotContains('$this->$method()', $groovy);
    }
    
    public function testDynamicAccess()
    {
        $code = "
class A {
    
    private \$a;
    private \$b;
    
    protected function a(\$arg) {
        return \$this->\$arg;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('this."$arg"', $groovy);
        $this->assertNotContains('$this->$arg', $groovy);
    }
    
    public function testMagicCall()
    {
        $code = "
class A {
    
    public function __call(\$name, \$arguments) {
        return 'test';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('def methodMissing(String name, def arguments)', $groovy);
        $this->assertNotContains('__call', $groovy);
    }
    
    public function testParamTypeDocComment1()
    {
        $code = "
class A {
    
    /**
     * @param string \$name
     * @param array \$arguments
     * @return string
     */
    public function test(\$name, \$arguments) {
        return 'test';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('public String test(String name, def arguments)', $groovy);
    }
    
    public function testParamTypeDocComment2()
    {
        $code = "
class A {
    
    /**
     * @param bool \$flag
     * @return void
     */
    public function test(\$flag) {
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('public void test(boolean flag)', $groovy);
    }
    
    public function testCatch()
    {
        $code = "
class A {
    
    public function test(\$flag) {
        try {
            test();
        } catch (Error \$e) {
        }
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('catch (Error e)', $groovy);
    }
    
    public function testUseImport()
    {
        $code = "
            
use B\BClass;

class A {
    
    public function test(BClass \$a) {

    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('import B.BClass', $groovy);
        $this->assertNotContains('use B\BClass', $groovy);
    }
    
    public function testStrictComparison()
    {
        $code = "
class A {
    
    public function test(\$flag) {
        if (\$flag === this)
            return;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('flag == this', $groovy);
        $this->assertNotContains('===', $groovy);
    }
    
    public function testStrictComparison2()
    {
        $code = "
class A {
    
    public function test(\$flag) {
        if (\$flag !== this)
            return;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('flag != this', $groovy);
        $this->assertNotContains('!==', $groovy);
    }
    
    public function testNullCoalescing()
    {
        $code = "
class A {
    
    public function test(\$b) {
        \$a = \$b ?? 'fallback';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("b ?: 'fallback'", $groovy);
        $this->assertNotContains("b ?? 'fallback'", $groovy);
    }
    
    public function testLiteralAndOrXor()
    {
        $code = "
class A {
    
    public function test(\$a, \$b, \$c) {
        if (\$a xor \$b)
            return \$a and \$b or \$c;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("a ^ b", $groovy);
        $this->assertContains("a && b || c", $groovy);
        $this->assertNotContains("a xor b", $groovy);
        $this->assertNotContains("a and b or c", $groovy);
    }
    
    public function testIntegerArray()
    {
        $code = "\$b = array(1,2,3,5);";
        $groovy = $this->parse($code);
        $this->assertContains("b = [1, 2, 3, 5]", $groovy);
        $this->assertNotContains("array", $groovy);
    }
    
    public function testFlatAssocArrayIsMap()
    {
        $code = "\$b = array('a' => 1, 'b' => 2);";
        $groovy = $this->parse($code);
        $this->assertContains("b = [a: 1, b: 2]", $groovy);
        $this->assertNotContains("array('a' => 1, 'b' => 2)", $groovy);
    }
    
    public function testYieldNotSupported()
    {
        $code = "function gen() {
    for (\$i = 1; \$i <= 3; \$i++) {
        yield \$i;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("throw new GroovyException('(yield i) is not supported')", $groovy);
    }
    
    public function testDestructor()
    {
        $code = "
class A {
    
    public function __destruct() {
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("public def close", $groovy);
        $this->assertNotContains("__destruct", $groovy);
    }
    
    public function testDie()
    {
        $code = "die();";
        $groovy = $this->parse($code);
        $this->assertContains("throw new GroovyException('die')", $groovy);
        $this->assertNotContains("die()", $groovy);
    }
    
    public function testDieWithArg()
    {
        $code = "die('no');";
        $groovy = $this->parse($code);
        $this->assertContains("throw new GroovyException('no')", $groovy);
        $this->assertNotContains("die('no')", $groovy);
    }
    
    public function testExit1()
    {
        $code = "exit(1);";
        $groovy = $this->parse($code);
        $this->assertContains("System.exit(1)", $groovy);
    }
    
    public function testCastArray()
    {
        $code = "\$tmp = (array)\$x;";
        $groovy = $this->parse($code);
        $this->assertContains("tmp = x as Object[]", $groovy);
    }
    
    public function testUnsetNulls()
    {
        $code = "unset(\$x[0], \$x[1]);";
        $groovy = $this->parse($code);
        $this->assertContains("x[0] = null", $groovy);
        $this->assertContains("x[1] = null", $groovy);
        $this->assertNotContains("unset", $groovy);
    }
    
    public function testGlobalConst()
    {
        $code = "const A = 'b';";
        $groovy = $this->parse($code);
        $this->assertContains("@Field String A = 'b'", $groovy);
        $this->assertContains("import groovy.transform.Field", $groovy);
        $this->assertNotContains("const", $groovy);
    }
    
    public function testBoolToBooleanWithFunction()
    {
        $code = "function a (bool \$b) : bool 
            {
            return false;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains("boolean a(boolean b)", $groovy);
        $this->assertNotContains("bool b", $groovy);
    }
    
    public function testLowerCaseStringTypeParam()
    {
        $code = "function a (string \$b) : bool 
            {
            return false;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains("boolean a(String b)", $groovy);
        $this->assertNotContains("string", $groovy);
    }
    
    public function testMultilineString()
    {
        $code = "function a() 
            {
                \$a = '
                This
                is
                multiline';
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains('"""', $groovy);
        $this->assertContains('multiline"""', $groovy);
        $this->assertNotContains("'", $groovy);
        $this->assertNotContains(" '\n", $groovy);
    }
    
    public function testMultilineString2()
    {
        $code = "function a() 
            {
                \$a = \"
                This
                is
                multiline\";
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains('"""\n', $groovy);
        $this->assertContains('multiline"""', $groovy);
        $this->assertNotContains(" '\n", $groovy);
    }
    
    public function testHereDoc()
    {
        $code = "function a() 
            {
                \$a = <<<EOT
This is a text
EOT;
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains('"""' . "\n", $groovy);
        $this->assertNotContains("EOT", $groovy);
        $this->assertNotContains(">", $groovy);
        $this->assertNotContains(">", $groovy);
    }
    
    public function testNowDoc()
    {
        $code = "function a() 
            {
                \$a = <<<'EOT'
This is a text
EOT;
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains('"""'. "\n", $groovy);
        $this->assertNotContains("'EOT'", $groovy);
        $this->assertNotContains("EOT", $groovy);
        $this->assertNotContains(">", $groovy);
        $this->assertNotContains(">", $groovy);
    }
    
    public function testClosure()
    {
        $code = "\$a = function(string \$b, int \$c) 
            {
            return \$b;
            };
            ";
        $groovy = $this->parse($code);
        $this->assertContains('{ String b, int c ->', $groovy);
        $this->assertNotContains("function", $groovy);
        $this->assertNotContains("(", $groovy);
        $this->assertNotContains(")", $groovy);
    }
    
    
    /**
     * @param string $code without leading <?php 
     * @return string
     */
    private function parse(string $code): string
    {
        return $this->tusk->toGroovy($this->tusk->getStatements("<?php " . $code));
    }

    private function normalizeInvisibleChars(string $str) : string
    {
        return  str_replace(PHP_EOL, "",  str_replace("\t", "", str_replace(" ", "", $str)));
    }
}
