<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Engines\CompilerEngine;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ViewCompilerEngineTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testViewsMayBeRecompiledAndRendered()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->with(__DIR__.'/fixtures/foo.php')->andReturn(true);
        $engine->getCompiler()->shouldReceive('compile')->once()->with(__DIR__.'/fixtures/foo.php');
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testViewsAreNotRecompiledIfTheyAreNotExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->andReturn(false);
        $engine->getCompiler()->shouldReceive('compile')->never();
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testViewsAreNotRecompiledIfWeDoNotWantThemRecompiled()
    {
        $engine = $this->getEngine(false);
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->never();
        $engine->getCompiler()->shouldReceive('compile')->never();
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    protected function getEngine($checkExpiredViews = true)
    {
        return new CompilerEngine(m::mock(CompilerInterface::class), $checkExpiredViews);
    }
}
