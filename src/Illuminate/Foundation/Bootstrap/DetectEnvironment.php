<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use InvalidArgumentException;
use Illuminate\Contracts\Foundation\Application;

class DetectEnvironment
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if (! $app->configurationIsCached()) {
            try {
                (new Dotenv($app->environmentPath(), $app->environmentFile()))->load();
            } catch (InvalidArgumentException $e) {
                //
            }
        }
    }
}
