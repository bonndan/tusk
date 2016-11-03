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

    /**
     * @param string $code without leading <?php 
     * @return string
     */
    private function parse(string $code): string
    {
        $stmts = $this->parser->parse("<?php " . $code);
        return $this->printer->prettyPrint($stmts);
    }

}
