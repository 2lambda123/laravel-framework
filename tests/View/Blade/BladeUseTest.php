<?php

namespace Illuminate\Tests\View\Blade;

class BladeUseTest extends AbstractBladeTestCase
{
    public function testUseStatementsAreCompiled()
    {
        $string = "Foo @use('SomeNamespace\SomeClass', 'Foo') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithoutAsAreCompiled()
    {
        $string = "Foo @use('SomeNamespace\SomeClass') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithBackslashAtBeginningAreCompiled()
    {
        $string = "Foo @use('\SomeNamespace\SomeClass', 'Foo') bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithArrayAreCompiled()
    {
        $string = "Foo @use(['SomeNamespace\SomeClass', 'AnotherNamespace\AnotherClass']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; use \AnotherNamespace\AnotherClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithArrayAndBackslashAtBeginningAreCompiled()
    {
        $string = "Foo @use(['\SomeNamespace\SomeClass', '\AnotherNamespace\AnotherClass']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; use \AnotherNamespace\AnotherClass; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithArrayAndAsAreCompiled()
    {
        $string = "Foo @use(['SomeNamespace\SomeClass' => 'Foo', 'AnotherNamespace\AnotherClass' => 'Bar']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass as Foo; use \AnotherNamespace\AnotherClass as Bar; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testUseStatementsWithArrayAndAsAreCompiledWithNumericKeys()
    {
        $string = "Foo @use(['SomeNamespace\SomeClass', 'AnotherNamespace\AnotherClass' => 'Bar']) bar";
        $expected = "Foo <?php use \SomeNamespace\SomeClass; use \AnotherNamespace\AnotherClass as Bar; ?> bar";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
