<?php

namespace Illuminate\Tests\View\Blade;

class BladePhpStatementsTest extends AbstractBladeTestCase
{
    public function testPhpStatementsWithExpressionAreCompiled()
    {
        $string = '@php($set = true)';
        $expected = '<?php ($set = true); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsWithoutExpressionAreIgnored()
    {
        $string = '@php';
        $expected = '@php';
        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = '{{ "Ignore: @php" }}';
        $expected = '<?php echo e("Ignore: @php"); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPhpStatementsDontParseBladeCode()
    {
        $string = '@php echo "{{ This is a blade tag }}" @endphp';
        $expected = '<?php echo "{{ This is a blade tag }}" ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testVerbatimAndPhpStatementsDontGetMixedUp()
    {
        $string = "@verbatim {{ Hello, I'm not blade! }}"
                ."\n@php echo 'And I'm not PHP!' @endphp"
                ."\n@endverbatim {{ 'I am Blade' }}"
                ."\n@php echo 'I am PHP {{ not Blade }}' @endphp";

        $expected = " {{ Hello, I'm not blade! }}"
                ."\n@php echo 'And I'm not PHP!' @endphp"
                ."\n <?php echo e('I am Blade'); ?>"
                ."\n\n<?php echo 'I am PHP {{ not Blade }}' ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testStringWithParenthesisCanBeCompiled()
    {
        $string = "@php(\$data = ['single' => ')'])";
        $expected = "<?php (\$data = ['single' => ')']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = ['(multiple)-))' => '((-))'])";
        $expected = "<?php (\$data = ['(multiple)-))' => '((-))']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = [(int)'(multiple)-))' => (bool)'((casty))'])";
        $expected = "<?php (\$data = [(int)'(multiple)-))' => (bool)'((casty))']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $this->assertSame('<?php echo $__env->renderEach(\'foo\', \'b)a)r\'); ?>', $this->compiler->compileString('@each(\'foo\', \'b)a)r\')'));
        $this->assertSame('<?php echo $__env->make(\'test_for\', [\'issue))\' => \'(issue#45424))\'], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@include(\'test_for\', [\'issue))\' => \'(issue#45424))\'])'));
        $this->assertSame('( <?php echo $__env->make(\'test_for\', [\'not_too_much))\' => \'(issue#45424))\'], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>))', $this->compiler->compileString('( @include(\'test_for\', [\'not_too_much))\' => \'(issue#45424))\'])))'));
    }

    public function testStringWithEmptyStringDataValue()
    {
        $string = "@php(\$data = ['test' => ''])";

        $expected = "<?php (\$data = ['test' => '']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = ['test' => \"\"])";

        $expected = "<?php (\$data = ['test' => \"\"]); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testStringWithEscapingDataValue()
    {
        $string = "@php(\$data = ['test' => 'won\\'t break'])";

        $expected = "<?php (\$data = ['test' => 'won\\'t break']); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));

        $string = "@php(\$data = ['test' => \"\\\"escaped\\\"\"])";

        $expected = "<?php (\$data = ['test' => \"\\\"escaped\\\"\"]); ?>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
