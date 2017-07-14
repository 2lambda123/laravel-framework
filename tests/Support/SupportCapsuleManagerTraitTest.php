<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use Illuminate\Tests\AbstractTestCase as TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\CapsuleManagerTrait;

class SupportCapsuleManagerTraitTest extends TestCase
{
    use CapsuleManagerTrait;

    public function testSetupContainerForCapsule()
    {
        $this->container = null;
        $app = new Container;

        $this->assertNull($this->setupContainer($app));
        $this->assertEquals($app, $this->getContainer());
        $this->assertInstanceOf('Illuminate\Support\Fluent', $app['config']);
    }

    public function testSetupContainerForCapsuleWhenConfigIsBound()
    {
        $this->container = null;
        $app = new Container;
        $app['config'] = m::mock('Illuminate\Config\Repository');

        $this->assertNull($this->setupContainer($app));
        $this->assertEquals($app, $this->getContainer());
        $this->assertInstanceOf('Illuminate\Config\Repository', $app['config']);
    }
}
