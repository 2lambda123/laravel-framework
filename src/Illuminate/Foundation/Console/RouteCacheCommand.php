<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionMethod;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

class RouteCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'route:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a route cache file for faster route registration';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new route command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('route:clear');

        $routes = $this->getFreshApplicationRoutes();

        if (count($routes) === 0) {
            return $this->error("Your application doesn't have any routes.");
        }

        $symfonyRoutes = new SymfonyRouteCollection();
        $attributes = [];

        foreach ($routes as $route) {
            $route->prepareForSerialization();

            $name = $route->getName() ?? Str::random();

            $symfonyRoutes->add($name, $route->toSymfonyRoute());
            $attributes[$name] = [
                'uri' => $route->uri(),
                'action' => $route->getAction(),
            ];
        }

        $method = new ReflectionMethod(CompiledUrlMatcherDumper::class, 'generateCompiledRoutes');
        $method->setAccessible(true);

        $compiled = $method->invoke(new CompiledUrlMatcherDumper($symfonyRoutes));

        $this->files->put(
            $this->laravel->getCachedRoutesPath(),
            $this->buildRouteCacheFile($compiled, $attributes)
        );

        $this->info('Routes cached successfully!');
    }

    /**
     * Boot a fresh copy of the application and get the routes.
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    protected function getFreshApplicationRoutes()
    {
        return tap($this->getFreshApplication()['router']->getRoutes(), function ($routes) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        });
    }

    /**
     * Get a fresh application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function getFreshApplication()
    {
        return tap(require $this->laravel->bootstrapPath().'/app.php', function ($app) {
            $app->make(ConsoleKernelContract::class)->bootstrap();
        });
    }

    /**
     * Build the route cache file.
     *
     * @param  string  $compiledRoutes
     * @param  array  $attributes
     * @return string
     */
    protected function buildRouteCacheFile(string $compiledRoutes, array $attributes)
    {
        $stub = $this->files->get(__DIR__.'/stubs/routes.stub');

        $replaced = str_replace('{{compiledRoutes}}', $compiledRoutes, $stub);
        $replaced = str_replace('{{attributes}}', base64_encode(serialize($attributes)), $replaced);

        return $replaced;
    }
}
