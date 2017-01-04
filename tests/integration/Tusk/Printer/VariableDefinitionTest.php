<?php

require_once __DIR__ .'/TestCase.php';

/**
 * Tests the tusk printer.
 *
 */
class VariableDefinitionTest extends TestCase
{

    
    public function testForLoopNewCounter()
    {
        $code = '
for ($i=0;$i<10;$i++) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('for (def i = 0; i < 10; i++)', $groovy);
        $this->assertNotContains('for ($i=0;$i<10;$i++)', $groovy);
    }
    
    public function testForLoopCounterInScope()
    {
        $code = '
$i = 0;
for ($i=0;$i<10;$i++) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('def i = 0', $groovy);
        $this->assertContains('for (i = 0; i < 10; i++)', $groovy);
    }

    public function testForEachLoopWithKeyVarsDefined()
    {
        $code = '
$key = null;
$value = null;
$arr = [];
foreach ($arr as $key => $value) {
    continue;
}';

        $groovy = $this->parse($code);
        $this->assertContains('for (entry in arr) {', $groovy);
        $this->assertContains('key = (entry in Map.Entry) ? entry.key : arr.indexOf(entry)', $groovy);
        $this->assertContains('value = (entry in Map.Entry) ? entry.value : entry', $groovy);
        $this->assertNotContains('def key = entry.key', $groovy);
        $this->assertNotContains('def value = entry.value', $groovy);
    }
    
    public function testVarAssigned()
    {
        $code = "
class ABC {
    function a(){
        \$myArr = [];
    }
}"; 
        $groovy = $this->parse($code);
        $this->assertContains('def myArr = []', $groovy);
    }
   
    
    public function testParamNoDef()
    {
        $code = "
class A {
    
    /**
     * @param bool \$flag
     * @return void
     */
    public function test(string \$flag) {
    
        \$flag = 0;
    }
}";
        $groovy = $this->parse($code);
        $this->assertNotContains('def flag', $groovy);
    }
    
    public function testParamNoDefInIf()
    {
        $code = "
class A {
    
    /**
     * @param bool \$flag
     * @return void
     */
    public function test(\$flag) {
    
        if (something() > 0)
            \$flag = 0;
    }
}";
        $groovy = $this->parse($code);
        $this->assertNotContains('def flag', $groovy);
    }
 
    public function testIfNoDef()
    {
        $code = "
if(\$a = trim(\$b))
    echo \$a;
";
        $groovy = $this->parse($code);
        $this->assertContains("if ((a = trim(b)))", $groovy);
        $this->assertNotContains("def a", $groovy);
    }
    
    
    public function testAssignVar()
    {
        $code = "
class A {
    public function a()
    {
        \$a = \$this->getB();
        \$a = 0;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def a = getB()", $groovy);
        $this->assertContains("a = 0", $groovy);
        $this->assertNotContains("def a = 0", $groovy);
    }
    
    public function testAssignInIf()
    {
        $code = "
if (\$a = getB()) {
    echo \$a;
}
";
        $groovy = $this->parse($code);
        $this->assertContains("if ((a = getB()))", $groovy);
        $this->assertNotContains("if (def", $groovy);
        $this->assertNotContains("if ((def", $groovy);
    }
    
    public function testScopeVarsByChildrenRemainsLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        while (true) {
            \$b ='c';
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def b = 'c'", $groovy);
        $this->assertNotContains("defb/", $this->normalizeInvisibleChars($groovy));
    }
    
    public function testScopeVarsInConditionRemainLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        while (\$b ='c') {
            \$c = 1;
            echo \$c;
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("b = 'c'", $groovy);
        $this->assertNotContains("def b = 'c'", $groovy);
    }
    
    public function testScopeVarsForLoopRemainLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        for (\$i=0; \$i<2;\$i++) {
            echo \$i;
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("for (def i = ", $groovy);
    }
    
    public function testScopeVarsInForEachRemainLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        if (true)
            foreach (\$b as \$c) {
                echo \$c;
            }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("c in b", $groovy);
        $this->assertNotContains("def c", $groovy);
    }
    
    public function testScopeVarsInForEachKVRemainLocal1()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        if (true) {
            for (\$i =0; \$i <1; \$i++) {
                echo \$i;
            }
        }
        foreach (\$b as \$key => \$c) {
            return a(\$key);
        }
        
        for (\$i =0; \$i <1; \$i++) {
            echo \$i;
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertNotContains("defkeydef", $this->normalizeInvisibleChars($groovy));
        $this->assertNotContains("defcfor", $this->normalizeInvisibleChars($groovy));
    }
    
    
    public function testScopeVarsInForRemainLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        if (true)
            for (\$i =0; \$i <1; \$i++) {
                echo \$c;
            }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def i", $groovy);
        $this->assertNotContains("defifor", $this->normalizeInvisibleChars($groovy));
    }
    
    
    public function testScopeVarsInCatchRemainLocal()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        try {
            \$b ='c';
        }
        catch (Exception \$ex) {
            echo \$c;
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("Exception ex", $groovy);
        $this->assertNotContains("def ex", $groovy);
    }
    
    public function testScopeVarsByChildrenUsedLater()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        while (true) {
            \$b ='c';
        }
        
        echo \$b;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("defb", $this->normalizeInvisibleChars($groovy));
        $this->assertNotContains("def b = 'c'", $groovy);
    }
    
    public function testScopeVarsByChildrenNestedScopes()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        if (true) {
            switch (\$this->x) {
                case 1: \$b ='a'; break;
                case 2: \$b ='b'; break;
                case 3: \$b ='c'; break;
                case 4: \$b ='d'; break;
                default: \$b = 0;
            }
            
            echo \$b;
        }
        
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def b", $groovy);
        $this->assertNotContains("def b = '", $groovy);
    }
    
    public function testScopeVarsByChildrenUsedBefore()
    {
        $code = "
namespace ABC;

class A {
    
    public function a()
    {
        \$b = 'a';
        while (true) {
            if (true)
                \$b ='c';
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def b = 'a'", $groovy);
        $this->assertNotContains("def b = 'c'", $groovy);
    }

    /**
     * "def data" must not appear twice (not again in if-isset branch)
     */
    public function testEdgeCaseDeeperSubscopeDef()
    {
        $code = "
namespace ABC;

class A {
    
    public function xxx(string \$sqlWhere = 'teamID = 0', array \$params = array(), bool \$associate = true)
    {
        \$sql = array(
            'query' => 'SELECT p.playerID',
            'playerTable' => 'Players',
            'join' => '',
            'groupBy' => (isset(\$params['groupBy']) ? \$params['groupBy'] : array('playerID+')),
            'orderBy' => (isset(\$params['orderBy']) ? \$params['orderBy'] : ''),
        );
     
        if (true === \$associate) {
            \$data = \$this->getDB()->queryGrouped(\$sql['groupBy'], \$sql['query']);
        } else {
            \$data = \$this->getDB()->queryArray(\$sql['query']);
        }
        
        if (isset(\$params['injuryDetail']) && true === \$params['injuryDetail']) {
            \$injuries = \Controller::getInjuries();
            foreach (\$data as \$row) {
                \$row['injury'] = \$row['injuryID'] ? \$injuries->get(\$row['injuryID'])['translation_key'] : null;
            } 
        }
        
        return \$data;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("defdatadefsql", $this->normalizeInvisibleChars($groovy));
        $this->assertNotContains("defdatadefinjuries", $this->normalizeInvisibleChars($groovy));
    }
    
    /**
     * "def data" must not appear within foreach loop
     */
    public function testEdgeCaseForeach()
    {
        $code = "
namespace ABC;

class A {
    
    public function xxx(array \$teamIDs)
    {
        if (empty(\$teamIDs)) {
            return false;
        }

        \$data = array();
        \$tmp = \$this->getPlayer('teamID IN (' . implode(',', \$teamIDs) . ')', array('fields' => array('name')));
        if (!empty(\$tmp)) {
            foreach (\$tmp as \$playerID => \$data) {
                \$names[\$playerID] = \$this->decodeName(\$data['name']);
            }

            return \$names;
        }

        return false;
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("defdata=[]", $this->normalizeInvisibleChars($groovy));
        $this->assertNotContains("defdata=(entry", $this->normalizeInvisibleChars($groovy));
    }
    
    public function testClassVarClash()
    {
        $code = "
namespace ABC;

class A {
    
    protected \$systems;
    
    public function test(array \$x)
    {
        \$systems = [];
        foreach (\$x as \$y) {
            \$systems[] = \$y;
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def systems = []", $groovy);
    }
    
    public function testNoDefInCondition()
    {
        $code = "
namespace ABC;

class A {
    
    private static \$shortNames;
    
    private static function init(\$data)
    {
        if (empty(\$data['u']) || empty(\$data['t']) || !\$this->checkChangeToken(\$data['u'], \$data['t'])) {
            \$err = 'tokenInvalid';
        }

        if (!\$err && true !== \$err2 = \Controller::getFormManager()->validatePassword(\$data['p1'])) {
            \$err = \$err2;
        }

    }
}
";
        $groovy = $this->parse($code);
        $this->assertNotContains("def err2", $groovy);
    }
 
       
    public function testDefOnlyIf()
    {
        $code = "
namespace ABC;

class A {
    
    private static \$shortNames;
    
    private static function init()
    {
        if (isset(self::\$shortNames[strtolower(\$cls)])) {
            \$className = self::\$shortNames[strtolower(\$cls)];
        } else {
            \$className = '';
        }
    }
}
";
        $groovy = $this->parse($code);
        $this->assertContains("def className = shortNames[", $groovy);
        $this->assertContains("def className = ''", $groovy);
    }
}

