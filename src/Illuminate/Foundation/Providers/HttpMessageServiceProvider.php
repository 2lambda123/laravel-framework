<?php namespace Illuminate\Foundation\Providers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\HttpMessageInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class HttpMessageServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHttpFoundationFactory();
        $this->registerHttpMessageFactory();

        $this->app->alias('psr7.http_foundation_factory', HttpFoundationFactory::class);
        $this->app->alias('psr7.http_message_factory', HttpMessageFactoryInterface::class);

        $this->app->bind(RequestInterface::class, function ($app) {
            $factory = $app->make('psr7.http_message_factory');
            return $factory->createRequest($app['request']);
        });

        $this->app->alias(RequestInterface::class, ServerRequestInterface::class);
        $this->app->alias(RequestInterface::class, HttpMessageInterface::class);
    }

    public function registerHttpFoundationFactory()
    {
        $this->app->bind('psr7.http_foundation_factory', function () {
            return new HttpFoundationFactory();
        });
    }

    public function registerHttpMessageFactory()
    {
        $this->app->bind('psr7.http_message_factory', function () {
            return new DiactorosFactory();
        });
    }

    public function boot(Dispatcher $dispatcher)
    {
        $dispatcher->listen('router.prepare', function ($request, $response) {
            if ($response instanceof ResponseInterface) {
                $factory = $this->app->make('psr7.http_foundation_factory');
                return $factory->createResponse($response);
            }

            return $response;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'psr7.http_foundation_factory',
            'psr7.http_message_factory',
            HttpFoundationFactory::class,
            HttpMessageFactoryInterface::class,
            RequestInterface::class,
            ServerRequestInterface::class,
            HttpMessageInterface::class,
        ];
    }
}
