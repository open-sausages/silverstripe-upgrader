<?php

namespace SilverStripe\Upgrader\Tests\UpgradeRule\PHP\Visitor;

use SilverStripe\Upgrader\UpgradeRule\PHP\Visitor\ClassWarningsVisitor;
use SilverStripe\Upgrader\Util\ApiChangeWarningSpec;

class ClassWarningsVisitorTest extends BaseVisitorTest
{
    /**
     * @runInSeparateProcess
     */
    public function testBaseClass()
    {
        $this->scaffoldMockClass('MyNamespace\\SomeClass');

        // Mock myclass
        $myclass = <<<PHP
<?php

namespace MyNamespace;

class MyClass extends SomeClass
{
}
PHP;
        $item = $this->getMockFile($myclass, 'MyClass.php');

        $visitor = new ClassWarningsVisitor([
            new ApiChangeWarningSpec('MyNamespace\\SomeClass', 'Error with SomeClass')
        ], $item);

        $this->traverseWithVisitor($item, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertContains('Error with SomeClass', $warnings[0]->getMessage());
        $this->assertContains('class MyClass extends SomeClass', $this->getLineForWarning($myclass, $warnings[0]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testBaseClassWithNamespace()
    {
        $this->scaffoldMockClass('SomeNamespace\\SomeClass');

        // Mock myclass
        $myclass = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\SomeClass;

class MyClass extends SomeClass
{
}
PHP;

        $item = $this->getMockFile($myclass, 'MyClass.php');

        $visitor = new ClassWarningsVisitor([
            (new ApiChangeWarningSpec('SomeNamespace\\SomeClass', 'Error with SomeNamespace\\SomeClass'))
        ], $item);

        $this->traverseWithVisitor($item, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertContains('Error with SomeNamespace\\SomeClass', $warnings[0]->getMessage());
        $this->assertContains('class MyClass extends SomeClass', $this->getLineForWarning($myclass, $warnings[0]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testBaseClassWithInlineNamespace()
    {
        $this->scaffoldMockClass('SomeNamespace\\SomeClass');

        // Mock myclass
        $myClass = <<<PHP
<?php

namespace MyNamespace;

class MyClass extends \SomeNamespace\SomeClass
{
}
PHP;

        $item = $this->getMockFile($myClass);
        $visitor = new ClassWarningsVisitor([
            (new ApiChangeWarningSpec('SomeNamespace\\SomeClass', 'Error with SomeNamespace\\SomeClass'))
        ], $item);

        $this->traverseWithVisitor($item, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertContains('Error with SomeNamespace\\SomeClass', $warnings[0]->getMessage());
        $this->assertContains(
            'class MyClass extends \SomeNamespace\\SomeClass',
            $this->getLineForWarning($myClass, $warnings[0])
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testStaticClassUse()
    {
        $this->scaffoldMockClass('SomeNamespace\\SomeClass');

        //
        $myClass = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\\SomeClass;

class MyClass
{
    function foo()
    {
        SomeClass::bar();
    }
}
PHP;

        $input = $this->getMockFile($myClass);
        $visitor = new ClassWarningsVisitor([
            (new ApiChangeWarningSpec('SomeNamespace\\SomeClass', 'Error with SomeNamespace\\SomeClass'))
        ], $input);

        $this->traverseWithVisitor($input, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertContains('Error with SomeNamespace\\SomeClass', $warnings[0]->getMessage());
        $this->assertContains('SomeClass::bar()', $this->getLineForWarning($myClass, $warnings[0]));
    }

    /**
     * @runInSeparateProcess
     */
    public function testInstanciation()
    {
        $this->scaffoldMockClass('SomeNamespace\\SomeClass');

        // Mock my class
        $myClass = <<<PHP
<?php

namespace MyNamespace;

use SomeNamespace\\SomeClass;

class MyClass
{
    function foo()
    {
        \$foo = new SomeClass();
    }
}
PHP;

        $input = $this->getMockFile($myClass);
        $visitor = new ClassWarningsVisitor([
            (new ApiChangeWarningSpec('SomeNamespace\\SomeClass', 'Error with SomeNamespace\\SomeClass'))
        ], $input);

        $this->traverseWithVisitor($input, $visitor);

        $warnings = $visitor->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertContains('Error with SomeNamespace\\SomeClass', $warnings[0]->getMessage());
        $this->assertContains('new SomeClass()', $this->getLineForWarning($myClass, $warnings[0]));
    }
}
