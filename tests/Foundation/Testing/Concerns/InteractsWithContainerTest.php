<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Orchestra\Testbench\TestCase;
use stdClass;

class InteractsWithContainerTest extends TestCase
{
    public function testWithoutViteBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)(['resources/js/app.js'])->toHtml());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesReactRefresh()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->reactRefresh());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesAsset()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->asset('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithViteRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Vite::class, $handler);

        $this->withoutVite();
        $instance = $this->withVite();

        $this->assertSame($handler, resolve(Vite::class));
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteReturnsEmptyArrayForPreloadedAssets(): void
    {
        $instance = $this->withoutVite();

        $this->assertSame([], app(Vite::class)->preloadedAssets());
        $this->assertSame($this, $instance);
    }

    public function testWithoutMixBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutMix();

        $this->assertSame('', (string) mix('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithMixRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Mix::class, $handler);

        $this->withoutMix();
        $instance = $this->withMix();

        $this->assertSame($handler, resolve(Mix::class));
        $this->assertSame($this, $instance);
    }

    public function testForgetMock()
    {
        $this->mock(InstanceStub::class)
            ->shouldReceive('execute')
            ->once()
            ->andReturn('bar');

        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());

        $this->forgetMock(InstanceStub::class);
        $this->assertSame('foo', $this->app->make(InstanceStub::class)->execute());
    }

    public function testMock()
    {
        $this->mock(InstanceStub::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn('bar');
        });
        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());
        $this->assertNull($this->app->make(InstanceStub::class)->property);

        $this->mock(InstanceStub::class)
            ->shouldReceive('execute')
            ->once()
            ->andReturn('baz');
        $this->assertSame('baz', $this->app->make(InstanceStub::class)->execute());
        $this->assertNull($this->app->make(InstanceStub::class)->property);

        $this->mock(InstanceStub::class, ['bar'], function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->andReturn('bar');
        });
        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());
        $this->assertSame('bar', $this->app->make(InstanceStub::class)->property);

        $this->mock(InstanceStub::class, ['baz'])
            ->shouldReceive('execute')
            ->once()
            ->andReturn('baz');
        $this->assertSame('baz', $this->app->make(InstanceStub::class)->execute());
        $this->assertSame('baz', $this->app->make(InstanceStub::class)->property);
    }
}

class InstanceStub
{
    public ?string $property = null;

    public function __construct($param = 'foo')
    {
        $this->property = $param;
    }

    public function execute()
    {
        return 'foo';
    }
}
