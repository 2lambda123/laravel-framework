<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class FoundationServiceProvidersTest extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [HeadServiceProvider::class];
    }

    /** @test */
    public function it_can_boot_service_provider_registered_from_another_service_provider()
    {
        $this->assertTrue($this->app['tail.registered']);
        $this->assertTrue($this->app['tail.booted']);
    }
}

class HeadServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->register(TailServiceProvider::class);
    }
}

class TailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['tail.registered'] = true;
    }

    public function boot()
    {
        $this->app['tail.booted'] = true;
    }
}
