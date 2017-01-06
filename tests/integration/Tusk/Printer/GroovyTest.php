<?php

require_once __DIR__ .'/TestCase.php';

/**
 * Tests the tusk printer.
 *
 */
class GroovyTest extends TestCase
{

    public function testClassConstString()
    {
        $code = "class abc {
    const ABC_DEF = '123';
}";

        $this->assertContains("public final static String ABC_DEF = '123'", $this->parse($code));
    }

    public function testClassConstInteger()
    {
        $code = "class abc {
    const ABC_DEF = 123;
}";

        $this->assertContains("public final static Integer ABC_DEF = 123", $this->parse($code));
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
        $this->assertContains("private def a", $groovy);
        $this->assertNOtContains(";", $groovy);
    }
    
    public function testClassPropertyNull()
    {
        $code = "class abc {
    private \$a = null;
}";

        $groovy = $this->parse($code);
        $this->assertContains("private def a", $groovy);
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
    
    public function testClassPropertyVar()
    {
        $code = "class abc {
    var \$a = '123';
}";

        $groovy = $this->parse($code);
        $this->assertContains("String a = '123'", $groovy);
        $this->assertNotContains("var String", $groovy);
        $this->assertNotContains("def String", $groovy);
    }
    
    public function testThisOmitted()
    {
        $code = "
class A 
{
    public function getA()
    {
        if (!\$this->data) {
            \$this->data = array_flip(\$this->repo->getData());
        }
        return \$this->data;
    }
}";

        $groovy = $this->parse($code);
        $this->assertContains("repo.getData()", $groovy);
        $this->assertContains("repo.getData()", $groovy);
        $this->assertContains("return data", $groovy);
        $this->assertNotContains("this.repo", $groovy);
        $this->assertNOtContains("this", $groovy);
    }
    
    public function testThisIsNotOmitted()
    {
        $code = "
class A 
{
    private \$data;
    public function setData(\$data)
    {
        \$this->data = \$data;
    }
}";

        $groovy = $this->parse($code);
        $this->assertContains("this.data = data", $groovy);
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
    
    public function testForEachLoopNested1()
    {
        $code = '
class A
{
    function a()
    {
        $arr = [];
        foreach ($arr as $key => $value) {
            foreach ($value as $x => $y) {
                echo $x;
            }
        }
    
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('entry_', $groovy);
    }
    
    public function testForEachLoopNestedBreak2()
    {
        $code = '
foreach ($arr as $value) {
    foreach ($value as $x) {
        break 2;
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('loop1:', $groovy);
        $this->assertContains('loop2:', $groovy);
        $this->assertContains('break loop1', $groovy);
    }
    
    public function testForEachLoopNestedContinue2()
    {
        $code = '
foreach ($arr as $value) {
    foreach ($value as $x) {
        continue 2;
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('loop1:', $groovy);
        $this->assertContains('loop2:', $groovy);
        $this->assertContains('continue loop1', $groovy);
    }
    
    public function testForLoopNestedContinue2()
    {
        $code = '
for ($a =1; $a < 2; $a++) {
    foreach ($value as $x) {
        continue 2;
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('loop1:', $groovy);
        $this->assertContains('loop2:', $groovy);
        $this->assertContains('continue loop1', $groovy);
    }
    
    public function testForEachLoopConflictingValueVar()
    {
        $code = '
namespace A;
class A  {

    public function test($a)
    {
        foreach ($this->values as $a) {
            echo $a;
        }
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('a_ in values', $groovy);
        $this->assertContains('a = a_', $groovy);
    }
    
    public function testWhileLoopNestedContinue2()
    {
        $code = '
while ($a) {
    foreach ($value as $x) {
        continue 2;
    }
}
';

        $groovy = $this->parse($code);
        $this->assertContains('loop1:', $groovy);
        $this->assertContains('loop2:', $groovy);
        $this->assertContains('continue loop1', $groovy);
    }
    
    public function testForEachLoopNestedUnlabeled()
    {
        $code = '
foreach ($arr as $value) {
    foreach ($value as $x) {
        $x++;
    }
}
';

        $groovy = $this->parse($code);
        $this->assertNotContains('loop1:', $groovy);
        $this->assertNotContains('loop2:', $groovy);
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
        $this->assertContains('def key = (entry in Map.Entry) ? entry.key : arr.indexOf(entry)', $groovy);
        $this->assertContains('def value = (entry in Map.Entry) ? entry.value : entry', $groovy);
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
        $this->assertContains('String test(String name, ', $groovy);
        $this->assertContains('def arguments)', $groovy);
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
        $this->assertContains('String test(def name)', $groovy);
    }
    
    public function testParamTypeDocCommentReturnLowerCaseString()
    {
        $code = "
class A {
    
    /**
     * @return relative path
     */
    public function test() {
        return 'test';
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('def test', $groovy);
        $this->assertNotContains('return test(', $groovy);
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
        $this->assertContains('void test(Boolean flag)', $groovy);
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
    
    public function testMultiCatch()
    {
        $code = "
class A {
    
    public function test(\$flag) {
        try {
            test();
        } catch (A|B \$e) {
        }
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('catch (A|B e)', $groovy);
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
    
    public function testUseMultiImport()
    {
        $code = "
            
use B\{BClass, CClass};

class A {
    
    public function test(BClass \$a) {

    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('import B.BClass', $groovy);
        $this->assertContains('import B.CClass', $groovy);
        $this->assertNotContains('use', $groovy);
    }
    
    public function testUseFunctionImport()
    {
        $code = "
use function B\hello;
";
        $groovy = $this->parse($code);
        $this->assertContains('import static B.hello', $groovy);
        $this->assertNotContains('use function', $groovy);
    }
    
    public function testUseAlias()
    {
        $code = "
use B\Hello as ABC;
";
        $groovy = $this->parse($code);
        $this->assertContains('import B.Hello as ABC', $groovy);
        $this->assertNotContains('use', $groovy);
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
    
    public function testDieImportsException()
    {
        $code = "namespace A;
class X {
    public function x() {
        die(1);
    }      
}";
        $groovy = $this->parse($code);
        $this->assertContains("import org.codehaus.groovy.GroovyException", $groovy);
    }
    
    public function testCastArray()
    {
        $code = "\$tmp = (array)\$x;";
        $groovy = $this->parse($code);
        $this->assertContains("tmp = x as Object[]", $groovy);
    }
    
    public function testCastBoolean()
    {
        $code = "\$tmp = (bool)\$x;";
        $groovy = $this->parse($code);
        $this->assertContains("(Boolean) x", $groovy);
    }
    
    public function testCastStringLowercase()
    {
        $code = "\$tmp = (string)\$x;";
        $groovy = $this->parse($code);
        $this->assertContains("(String) x", $groovy);
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
        $this->assertContains("Boolean a(Boolean b)", $groovy);
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
        $this->assertContains("Boolean a(String b)", $groovy);
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
        $this->assertContains('{ String b, Integer c ->', $groovy);
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
        $this->assertContains("if (!b)", $groovy);
        $this->assertNotContains("empty(", $groovy);
    }
    
    public function testLiteralNotEmptyIsOmitted()
    {
        $code = "
function a(\$b) {
    if (!empty(\$b))
        return 'n';
}
";
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
    
    public function testArrayWhitespaceKey()
    {
        $code = "
class A 
{
    const B = 'b';
    
    function foo()
    {
        return ['A and B' => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains('["A and B":', $groovy);
    }
    
    public function testArrayFuncCallKey()
    {
        $code = "
class A extends B
{
    function foo()
    {
        return [xxx('b') => 'foo'];
    }
}";
        $groovy = $this->parse($code);
        $this->assertContains("[(xxx('b')):", $groovy);
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
    
    public function testTraitPublicMethods()
    {
        $code = "trait A 
{
    private \$x;
    protected \$y;
    public \$z;
    
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
        $this->assertContains("private def x", $groovy);
        $this->assertContains("def y", $groovy);
        $this->assertContains("def z", $groovy);
        $this->assertContains("def a()", $groovy);
        $this->assertContains("def a()", $groovy);
        $this->assertContains("private def b()", $groovy);
        $this->assertContains("static def c()", $groovy);
        $this->assertNotContains("protected", $groovy);
    }
    
    public function testReservedWordVar()
    {
        $code = "
\$short = trim(\$b);
";
        $groovy = $this->parse($code);
        $this->assertContains("_short = trim(b)", $groovy);
    }
    
    public function testReservedWordClassName()
    {
        $code = "
class Short {

}
";
        $groovy = $this->parse($code);
        $this->assertContains("class _Short", $groovy);
        $this->assertContains("TODO", $groovy);
    }
    
    public function testReservedWordMethodCall()
    {
        $code = "
class A {
    public function a()
    {
        \$this->b();
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("b()", $groovy);
        $this->assertNotContains("this", $groovy);
    }
    
    public function testReservedWordParam()
    {
        $code = "
class A {
    public function a(\$new)
    {
        \$this->b(\$new);
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("a(def _new)", $groovy);
        $this->assertContains("b(_new)", $groovy);
        $this->assertNotContains("(new", $groovy);
    }
    
    public function testList()
    {
        $code = "
list(\$a, \$b) = \$data;
";
        $groovy = $this->parse($code);
        $this->assertContains("def(a, b) = data", $groovy);
        $this->assertContains("TODO", $groovy);
    }
    
    public function testRemoveSilence()
    {
        $code = "
@require 'test';
";
        $groovy = $this->parse($code);
        $this->assertContains("require", $groovy);
        $this->assertNotContains("@", $groovy);
    }
    
    public function testCastingPrecedenceBool()
    {
        $code = "
\$a = (bool) \$b == true;
";
        $groovy = $this->parse($code);
        $this->assertContains("((Boolean) b)", $groovy);
        $this->assertNotContains("b == true", $groovy);
    }
    
    public function testCastingPrecedenceDouble()
    {
        $code = "
\$a = (double) \$b > 0;
";
        $groovy = $this->parse($code);
        $this->assertContains("((double) b)", $groovy);
        $this->assertNotContains("b > 0", $groovy);
    }
    
    public function testCastingPrecedenceInt()
    {
        $code = "
\$a = (int) \$b > 0;
";
        $groovy = $this->parse($code);
        $this->assertContains("((int) b)", $groovy);
        $this->assertNotContains("b > 0", $groovy);
    }
    
    public function testCastingPrecedenceString()
    {
        $code = "
\$a = (string) \$b == 'x';
";
        $groovy = $this->parse($code);
        $this->assertContains("((String) b)", $groovy);
        $this->assertNotContains("b == x", $groovy);
    }
    
    public function testIssetToCoalesceOnAssigment()
    {
        $code = "
\$a = isset(\$b);
";
        $groovy = $this->parse($code);
        $this->assertContains("a = (b)?true:false", $groovy);
        $this->assertNotContains("a == isset(b)", $groovy);
    }
    
    public function testIssetRemovedInIf()
    {
        $code = "
if(isset(\$a))print(1);
if(isset(\$a, \$b))print(1);
if(\$a && isset(\$b)) print(1);
";
        $groovy = $this->parse($code);
        $this->assertContains("if (a)", $groovy);
        $this->assertContains("if (a && b)", $groovy);
        $this->assertContains("if (a && b)", $groovy);
    }
    
    public function testReturnTypeMixedToDef()
    {
        $code = "
class A {
    /**
     * @return mixed
     */
    public function a()
    {
        return \$this->b;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def a", $groovy);
        $this->assertNotContains("public mixed a", $groovy);
    }
    
    public function testReturnTypeNumericToDef()
    {
        $code = "
class A {
    /**
     * @return numeric
     */
    public function a()
    {
        return \$this->b;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def a", $groovy);
        $this->assertNotContains("numeric a", $groovy);
    }
    
    
    public function testTypeNumericToDef()
    {
        $code = "
class A {
    /**
     * @var numeric
     */
    private \$a;
}
";
        $groovy = $this->parse($code);
        $this->assertContains("private def a", $groovy);
        $this->assertNotContains("numeric a", $groovy);
    }
    
    public function testArrayKeyDynamic()
    {
        $code = "
\$a = [\$b + 'something' => 'foobar'];
";
        $groovy = $this->parse($code);
        $this->assertContains("a = [(b + 'something'): 'foobar']", $groovy);
    }
    
    public function testArrayKeySpecialChar()
    {
        $code = '
$bad = array(
    "javascript\s*:"    => "[removed]",
    "expression\s*\("   => "[removed]", // CSS and IE
    "Redirect\s+302"    => "[removed]",
    \'<!--\'              => \'&lt;!--\',
    \'-->\'               => \'--&gt;\',
    \'<!CDATA[\'          => \'&lt;![CDATA[\',
    \'5th\'          => 5
);
';
        $groovy = $this->parse($code);
        $this->assertContains('"javascript\s*:"', $groovy);
        $this->assertContains('"expression\s*\("', $groovy);
        $this->assertContains('"Redirect\s+302"', $groovy);
        $this->assertContains('"<!--"', $groovy);
        $this->assertContains('"-->"', $groovy);
        $this->assertContains('"<!CDATA["', $groovy);
        $this->assertContains('"5th"', $groovy);
    }
    
    public function testGlobalPostAccess()
    {
        $code = "
\$a = \$_POST['a'];
";
        $groovy = $this->parse($code);
        $this->assertContains("a = request.getParameterMap()['a']", $groovy);
    }
    
    public function testGlobalServerRemoteAddr()
    {
        $code = "
\$a = \$_SERVER['REMOTE_ADDR'];
";
        $groovy = $this->parse($code);
        $this->assertContains('a = request.getHeader("Remote_Addr")', $groovy);
    }
    
    public function testGlobalCookies()
    {
        $code = "
\$a = \$_COOKIE['x'];
";
        $groovy = $this->parse($code);
        $this->assertContains("a = request.getCookies()['x']", $groovy);
    }
    
    public function testGlobalEnv()
    {
        $code = "
\$a = \$_ENV['x'];
";
        $groovy = $this->parse($code);
        $this->assertContains("a = System.getenv()['x']", $groovy);
    }
    
    public function testGlobalsGet()
    {
        $code = "
\$a = \$_GET['x'];
";
        $groovy = $this->parse($code);
        $this->assertContains('a = URLDecoder.decode(request.getQueryString(), "UTF-8")[\'x\']', $groovy);
    }
    
    public function testServerHttps()
    {
        $code = "
\$a = \$_SERVER['HTTPS'];
";
        $groovy = $this->parse($code);
        $this->assertContains('a = request.getScheme() == "https" ? "on" : ""', $groovy);
    }
    
    public function testRequireInclude()
    {
        $code = "
require 'a.php';
require_once('b.php');
include 'c.php';
include_once('d.php');
";
        $groovy = $this->parse($code);
        $this->assertContains("// TODO require 'a.php'", $groovy);
        $this->assertContains("// TODO require_once", $groovy);
        $this->assertContains('// TODO include ', $groovy);
        $this->assertContains('// TODO include_once', $groovy);
    }
    
    public function testImportAlways()
    {
        $code = "
namespace ABC;
class A {
    
    public function a()
    {
        return 1;
    }
}
";
        $this->config->alwaysImport[] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertContains("import X.Y.ZClass", $groovy);
    }
    
    public function testImportOnDemandStaticCall()
    {
        $code = "
namespace ABC;
class A {
    
    public function a()
    {
        return TestClass::abc();
    }
}
";
        $this->config->onDemandImport['TestClass'] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertContains("import X.Y.ZClass", $groovy);
    }
    
    public function testImportOnDemandStaticProp()
    {
        $code = "
namespace ABC;
class A {
    
    public function a()
    {
        return TestClass::\$abc;
    }
}
";
        $this->config->onDemandImport['TestClass'] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertContains("import X.Y.ZClass", $groovy);
    }
    
    public function testImportOnDemandClassConst()
    {
        $code = "
namespace ABC;
class A {
    
    public function a()
    {
        return TestClass::ABC;
    }
}
";
        $this->config->onDemandImport['TestClass'] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertContains("import X.Y.ZClass", $groovy);
    }
    
    public function testImportOnDemandTypehint()
    {
        $code = "
namespace ABC;
class A {
    
    public function a(TestClass \$t)
    {
        return 1;
    }
}
";
        $this->config->onDemandImport['TestClass'] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertContains("import X.Y.ZClass", $groovy);
    }
    
    public function testImportOnDemandUsedTypehint()
    {
        $code = "
namespace ABC;
use Foo as TestClass;

class A {
    
    public function a(TestClass \$t)
    {
        return 1;
    }
}
";
        $this->config->onDemandImport['TestClass'] = 'X.Y.ZClass';
        $groovy = $this->parse($code);
        $this->assertNotContains("import X.Y.ZClass", $groovy);
    }
    

    public function testInterfaceDefaultValues()
    {
        $code = "
namespace ABC;

interface A {
    
    public function a(\$test = 0);
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def a()", $groovy);
        $this->assertContains("def a(def test)", $groovy);
        $this->assertNotContains("test = 0", $groovy);
    }
    
    public function testInterfaceMultipleDefaultValues()
    {
        $code = "
namespace ABC;

interface A {
    
    public function a(\$one, \$two = 0, \$three = 0);
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def a(def one)", $groovy);
        $this->assertContains("def a(def one, def two)", $groovy);
        $this->assertContains("def a(def one, def two, def three)", $groovy);
        $this->assertNotContains("two = 0", $groovy);
        $this->assertNotContains("three = 0", $groovy);
    }
  
    public function testNewInstanceDynamic()
    {
            $code = "
namespace ABC;

class Factory {
    
    public function make(\$className)
    {
        return new \$className('test');
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("this.class.classLoader.loadClass(className, true, false )?.newInstance('test')", $groovy);
        $this->assertNotContains("new \$className", $groovy);
    }
    
    public function testNewInstanceStatic()
    {
            $code = "
namespace ABC;

class Factory {
    
    public function make(\$p)
    {
        return new A(\$p);
    }
}
";
        $groovy = $this->parse($code);
        $this->assertNotContains("this.class.classLoader.loadClass", $groovy);
        $this->assertContains("new A", $groovy);
    }
    
    public function testNewInstanceSelf()
    {
            $code = "
namespace ABC;

class Factory {
    
    public function make(\$p)
    {
        return new self(\$p);
    }
}
";
        $groovy = $this->parse($code);
        $this->assertNotContains("new self", $groovy);
        $this->assertNotContains("this.class.classLoader.loadClass", $groovy);
        $this->assertContains("getClass().newInstance(p)", $groovy);
    }

    public function testSwitchUnbrokenCase()
    {
            $code = "
namespace ABC;

class X {
    
    public function test(\$p)
    {
        switch (\$p) {
            case 1: return;
            case 2: 
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("case2:break", $this->normalizeInvisibleChars($groovy));
    }
    
    public function testDoWhile()
    {
        $code = "
do {
    echo 'a';
} while (\$x > 0);
";
        $groovy = $this->parse($code);
        $this->assertContains("for (;;)", $groovy);
        $this->assertContains("if (!(x > 0)", $groovy);
        $this->assertNotContains("do {", $groovy);
        $this->assertNotContains("while (", $groovy);
    }
    
    public function testRemoveReference()
    {
        $code = "
\$data =& \$x;
";
        $groovy = $this->parse($code);
        $this->assertContains("data = x", $groovy);
        $this->assertNotContains("&", $groovy);
    }
    
    public function testEmptyConstructorHasBody()
    {
        $code = "
namespace ABC;

class X {
    
    public function __construct()
    {
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("X(){}", $this->normalizeInvisibleChars($groovy));
    }
    
    public function testMagicFunctionResolved()
    {
        $code = "
namespace ABC;

class X {
    
    public function xxx()
    {
        if (\$Type && method_exists(\$Type, __FUNCTION__)) {
            return \$Type->{__FUNCTION__}(\$item, \$content);
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("'xxx'", $groovy);
        $this->assertNotContains("__FUNCTION__", $groovy);
    }
    
    public function testMagicMethodResolved()
    {
        $code = "
namespace ABC;

class X {
    
    public function xxx()
    {
        if (\$Type && method_exists(\$Type, __METHOD__)) {
            return \$Type->{__METHOD__}(\$item, \$content);
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("'xxx'", $groovy);
        $this->assertNotContains("__METHOD__", $groovy);
    }
    
    
    public function testReplaceConst()
    {
        $code = "
        \$a = DIR_SEPERATOR;
        ";
        $this->config->replaceNames['DIR_SEPERATOR'] = "'/'";
        $groovy = $this->parse($code);
        $this->assertContains("a = '/'", $groovy);
    }
    
    public function testReplaceImport()
    {
        $code = "
namespace A;
use function somefunc;
class B
{
}
";
        $this->config->replaceNames['somefunc'] = 'A.B.somefunc';
        $groovy = $this->parse($code);
        $this->assertContains("import static A.B.somefunc", $groovy);
    }
}


