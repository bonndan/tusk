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
    
    public function testClassConstFetchWithoutSelf()
    {
        $code = "class abc {
    const ABC_DEF = 123;
    
    function a()
    {
        return self::ABC_DEF;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("return ABC_DEF", $groovy);
        $this->assertNotContains("self", $groovy);
        $this->assertNotContains(".", $groovy);
    }
    
    public function testStaticClassVarFetchWithoutSelf()
    {
        $code = "class abc {
    private static \$a = 123;
    
    function a()
    {
        return self::\$a;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("return a", $groovy);
        $this->assertNotContains("self", $groovy);
        $this->assertNotContains(".", $groovy);
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
        $this->assertContains('for (entry in arr) {', $groovy);
        $this->assertContains('if (entry in Map.Entry) {', $groovy);
        $this->assertContains('def key = entry.key', $groovy);
        $this->assertContains('def value = entry.value', $groovy);
        $this->assertNotContains('foreach', $groovy);
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
        $this->assertContains('"$method"()', $groovy);
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
    
    public function testParamMixedTypeDocComment()
    {
        $code = "
class A {
    
    /**
     * @param string|Name \$name
     * @return string
     */
    public function test(\$name) {
        return 'test';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('public String test(def name)', $groovy);
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
    
    public function testScalarTypeHintOverridesParam()
    {
        $code = "
class A {
    
    /**
     * @param bool \$flag
     * @return void
     */
    public function test(string \$flag) {
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('test(String flag)', $groovy);
    }
    
    public function testTypeHintOverridesParam()
    {
        $code = "
class A {
    
    /**
     * @param bool \$flag
     * @return void
     */
    public function test(string \$flag) {
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('test(String flag)', $groovy);
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
        $this->assertContains("close()", $groovy);
        $this->assertNotContains("__destruct()", $groovy);
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
    
    public function testMultilineStringEndsOnDoubleQuote()
    {
        $code = "function a() 
            {
                \$a ='
                This
                is
                \"multiline\"';
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertContains('multiline" """', $groovy);
    }
    
    public function testMultilineStringEndsOnDoubleQuote2()
    {
        $code = "function a() 
            {
                \$a = \"
                This
                is
                \\\"multiline\\\"\";
            return \$a;
            }
            ";
        $groovy = $this->parse($code);
        $this->assertNotContains('""""', $groovy);
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
    
    public function testOwnMethodCallWithoutThis()
    {
        $code = "
class A extends B {
    
    public function a() {
        return \$this->b();
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("return b()", $groovy);
        $this->assertNotContains("this.b()", $groovy);
    }
    
    public function testReturnNoSemicolon()
    {
        $code = "
class A {
    
    public function a() {
        return 'a';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("return 'a'" . PHP_EOL, $groovy);
        $this->assertNotContains("return 'a';", $groovy);
    }
    
    public function testEmptyIsOmitted()
    {
        $code = "
class A {
    
    public function a(\$b) {
        if (empty(\$b))
            return 'n';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("if (b)", $groovy);
        $this->assertNotContains("empty(", $groovy);
    }
    
    public function testEval()
    {
        $code = "eval('\$a =1+2;');";
        $groovy = $this->parse($code);
        $this->assertContains("evaluate(", $groovy);
        $this->assertContains("TODO", $groovy);
        $this->assertNotContains("eval(", $groovy);
    }
    
    public function testMagicClassConst()
    {
        
            $code = "
class A {
    
    public function a() {
        return __CLASS__;
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("this.getClass().getName()", $groovy);
        $this->assertNotContains("__CLASS__;", $groovy);
    }
    
    public function testArrayTypeHint()
    {
        
            $code = "
interface A {
    public function a(array \$data);
}";
        $groovy = $this->parse($code);
        $this->assertContains("def data)", $groovy);
        $this->assertNotContains("array", $groovy);
    }
    
    public function testGotoUnsupported()
    {
        $code = "goto start;";
        $groovy = $this->parse($code);
        $this->assertContains("GroovyException", $groovy);
    }
    
    public function testGlobalUnsupported()
    {
        $code = "global \$a;";
        $groovy = $this->parse($code);
        $this->assertContains("GroovyException", $groovy);
    }
    
    public function testElseif()
    {
        $code = "if (\$a) {
            echo 1;
        } elseif (\$b) {
            echo 2;
        } else {
            echo 3;
}";
        $groovy = $this->parse($code);
        $this->assertContains('else if', $groovy);
        $this->assertNotContains('elseif', $groovy);
    }
    
    public function testArrayClassConstKey()
    {
        $code = "
class A 
{
    const B = 'b';
    
    function foo()
    {
        return [A::B => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('(A.B)', $groovy);
        $this->assertNotContains('A::B', $groovy);
    }
    
    public function testArrayOwnClassConstKey()
    {
        $code = "
class A 
{
    const B = 'b';
    
    function foo()
    {
        return [self::B => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('[(B):', $groovy);
        $this->assertNotContains('self::B', $groovy);
    }
    
    public function testArrayFuncCallKey()
    {
        $code = "
class A extends B
{
    const B = 'b';
    
    function foo()
    {
        return [strtolower('b') => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("[(strtolower('b')):", $groovy);
    }
    
    public function testArrayMethodCallKey()
    {
        $code = "
class A extends B
{
    const B = 'b';
    
    function foo()
    {
        return [\$this->bar() => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('[(bar()):', $groovy);
    }
    
    public function testArrayConcatKey()
    {
        $code = "
\$a = '1';
\$b = ['key' . \$a => 'foo'];
";
        $groovy = $this->parse($code);
        $this->assertContains("'key' + a", $groovy);
    }
    
    public function testHashComment()
    {
        $code = "
\$a = '1';
#\$a = 2;
";
        $groovy = $this->parse($code);
        $this->assertContains("//\$a", $groovy);
        $this->assertNotContains("#", $groovy);
    }
    
    public function testInvalidArgumentException()
    {
        $code = "
throw new InvalidArgumentException('test');
";
        $groovy = $this->parse($code);
        $this->assertContains("IllegalArgumentException", $groovy);
        $this->assertNotContains("InvalidArgumentException", $groovy);
    }
    
    public function testTraitPublicMethods()
    {
        $code = "trait A 
{
    protected function a(){
        return 0;
    }
    
    private function b(){
        return 0;
    }
    
    protected static function c(){
        return 0;
    }

}
";
        $groovy = $this->parse($code);
        $this->assertContains("public def a()", $groovy);
        $this->assertContains("public def b()", $groovy);
        $this->assertContains("public static def c()", $groovy);
        $this->assertNotContains("protected", $groovy);
        $this->assertNotContains("private", $groovy);
    }
    
    public function testIfExpressionEval()
    {
        $code = "
if(\$a = trim(\$b))
    echo \$a;
";
        $groovy = $this->parse($code);
        $this->assertContains("if ((a = trim(b)))", $groovy);
        $this->assertNotContains("if (a = trim(b))", $groovy);
    }
    
    /**
     * @param string $code without leading <?php 
     * @return string
     */
    private function parse(string $code): string
    {
        $state = new Tusk\State('test');
        return $this->tusk->toGroovy(
            $this->tusk->getStatements("<?php " . $code, $state),
            $state
        );
    }

    private function normalizeInvisibleChars(string $str) : string
    {
        return  str_replace(PHP_EOL, "",  str_replace("\t", "", str_replace(" ", "", $str)));
    }
}
