<?php

namespace Illuminate\Tests\View;

use Illuminate\Tests\AbstractTestCase as TestCase;
use Illuminate\View\Engines\PhpEngine;

class ViewPhpEngineTest extends TestCase
{
    public function testViewsMayBeProperlyRendered()
    {
        $engine = new PhpEngine;
        $this->assertEquals('Hello World
', $engine->get(__DIR__.'/fixtures/basic.php'));
    }
}
