<?php

namespace Illuminate\Foundation\Configuration;

use Closure;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as AppEventServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as AppRouteServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

class ApplicationBuilder
{
    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     */
    public function withKernels()
    {
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Illuminate\Foundation\Http\Kernel::class,
        );

        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \Illuminate\Foundation\Console\Kernel::class,
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $providers
     * @param  bool  $loadPackageProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $loadPackageProviders = true)
    {
        RegisterProviders::merge(
            $providers,
            $loadPackageProviders
                ? $this->app->bootstrapPath('providers.php')
                : null
        );

        return $this;
    }

    /**
     * Register the core event service provider for the application.
     *
     * @return $this
     */
    public function withEvents()
    {
        $this->app->booting(function () {
            $this->app->register(AppEventServiceProvider::class);
        });

        return $this;
    }

    /**
     * Register the braodcasting services for the application.
     *
     * @param  string  $channels
     * @return $this
     */
    public function withBroadcasting(string $channels)
    {
        $this->app->booted(function () use ($channels) {
            Broadcast::routes();

            if (file_exists($channels)) {
                require $channels;
            }
        });

        return $this;
    }

    /**
     * Register the routing services for the application.
     *
     * @param  \Closure|null  $using
     * @param  string|null  $web
     * @param  string|null  $api
     * @param  string|null  $apiPrefix
     * @param  callable|null  $then
     * @return $this
     */
    public function withRouting(?Closure $using = null,
        ?string $web = null,
        ?string $api = null,
        ?string $commands = null,
        ?string $channels = null,
        string $apiPrefix = 'api',
        ?callable $then = null)
    {
        if (is_null($using) && (is_string($web) || is_string($api))) {
            $using = function () use ($web, $api, $apiPrefix, $then) {
                if (is_string($api)) {
                    Route::middleware('api')->prefix($apiPrefix)->group($api);
                }

                if (is_string($web)) {
                    Route::middleware('web')->group($web);
                }

                if (is_callable($then)) {
                    $then();
                }
            };
        }

        AppRouteServiceProvider::loadRoutesUsing($using);

        $this->app->booting(function () {
            $this->app->register(AppRouteServiceProvider::class);
        });

        if (! is_null($commands)) {
            $this->withCommands([$commands]);
        }

        if (! is_null($channels)) {
            $this->withBroadcasting($channels);
        }

        return $this;
    }

    /**
     * Register the global middleware, middleware groups, and middleware aliases for the application.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function withMiddleware(callable $callback)
    {
        $this->app->afterResolving(HttpKernel::class, function ($kernel) use ($callback) {
            $middleware = (new Middleware)
                ->auth(redirectTo: fn () => route('login'))
                ->guest(redirectTo: fn () => route('dashboard'));

            $callback($middleware);

            $kernel->setGlobalMiddleware($middleware->getGlobalMiddleware());
            $kernel->setMiddlewareGroups($middleware->getMiddlewareGroups());
            $kernel->setMiddlewareAliases($middleware->getMiddlewareAliases());
        });

        return $this;
    }

    /**
     * Register additional Artisan commands with the application.
     *
     * @param  array  $commands
     * @return $this
     */
    public function withCommands(array $commands = [])
    {
        if (empty($commands)) {
            $commands = [$this->app->path('Console/Commands')];
        }

        $this->app->afterResolving(ConsoleKernel::class, function ($kernel) use ($commands) {
            [$commands, $paths] = collect($commands)->partition(fn ($command) => class_exists($command));
            [$routes, $paths] = $paths->partition(fn ($path) => is_file($path));

            $kernel->addCommands($commands->all());
            $kernel->addCommandPaths($paths->all());
            $kernel->addCommandRoutePaths($routes->all());
        });

        return $this;
    }

    /**
     * Register additional Artisan route paths.
     *
     * @param  array  $paths
     * @return $this
     */
    protected function withCommandRouting(array $paths)
    {
        $this->app->afterResolving(ConsoleKernel::class, function ($kernel) use ($paths) {
            $kernel->setCommandRoutePaths($paths);
        });
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \Illuminate\Foundation\Exceptions\Handler::class
        );

        $using ??= fn () => true;

        $this->app->afterResolving(
            \Illuminate\Foundation\Exceptions\Handler::class,
            fn ($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }

    /**
     * Register an array of container bindings to be bound when the application is booting.
     *
     * @param  array  $bindings
     * @return $this
     */
    public function withBindings(array $bindings)
    {
        return $this->registered(function ($app) use ($bindings) {
            foreach ($bindings as $abstract => $concrete) {
                $app->bind($abstract, $concrete);
            }
        });
    }

    /**
     * Register an array of singleton container bindings to be bound when the application is booting.
     *
     * @param  array  $singletons
     * @return $this
     */
    public function withSingletons(array $singletons)
    {
        return $this->registered(function ($app) use ($singletons) {
            foreach ($singletons as $abstract => $concrete) {
                if (is_string($abstract)) {
                    $app->singleton($abstract, $concrete);
                } else {
                    $app->singleton($concrete);
                }
            }
        });
    }

    /**
     * Register a callback to be invoked when the application is "booting".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booting(callable $callback)
    {
        $this->app->booting($callback);

        return $this;
    }

    /**
     * Register a callback to be invoked when the application is "booted".
     *
     * @param  callable  $callback
     * @return $this
     */
    public function booted(callable $callback)
    {
        $this->app->booted($callback);

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function create()
    {
        return $this->app;
    }
}
