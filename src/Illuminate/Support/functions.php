<?php

namespace Illuminate\Support;

use Illuminate\Support\Defer\DeferredCallback;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Process\PhpExecutableFinder;

/**
 * Defer execution of the given callback.
 *
 * @param  callable|null  $callback
 * @param  string|null  $name
 * @param  bool  $always
 * @return \Illuminate\Support\Defer\DeferredCallback
 */
function defer(?callable $callback = null, ?string $name = null, bool $always = false, bool|callable $conditional = true)
{
    if ($callback === null) {
        return app(DeferredCallbackCollection::class);
    }

    return tap(
        new DeferredCallback($callback, $name, $always, $conditional),
        fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
    );
}

/**
 * Determine the PHP Binary.
 *
 * @return string
 */
function php_binary()
{
    return (new PhpExecutableFinder)->find(false) ?: 'php';
}
