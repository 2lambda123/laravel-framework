<?php

namespace Illuminate\Foundation;

use Laravel;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class Mix
{
    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString|string
     *
     * @throws \Exception
     */
    public function __invoke($path, $manifestDirectory = '')
    {
        static $manifests = [];

        if (! Str::startsWith($path, '/')) {
            $path = "/{$path}";
        }

        if ($manifestDirectory && ! Str::startsWith($manifestDirectory, '/')) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        if (file_exists(Laravel::publicPath($manifestDirectory.'/hot'))) {
            $url = rtrim(file_get_contents(Laravel::publicPath($manifestDirectory.'/hot')));

            if (Str::startsWith($url, ['http://', 'https://'])) {
                return new HtmlString(Str::after($url, ':').$path);
            }

            return new HtmlString("//localhost:8080{$path}");
        }

        $manifestPath = Laravel::publicPath($manifestDirectory.'/mix-manifest.json');

        if (! isset($manifests[$manifestPath])) {
            if (! file_exists($manifestPath)) {
                throw new Exception('The Mix manifest does not exist.');
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        if (! isset($manifest[$path])) {
            $exception = new Exception("Unable to locate Mix file: {$path}.");

            if (! Laravel::app('config')->get('app.debug')) {
                Laravel::report($exception);

                return $path;
            } else {
                throw $exception;
            }
        }

        return new HtmlString($manifestDirectory.$manifest[$path]);
    }
}
