<?php

namespace Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository|\Illuminate\Contracts\Cache\LockProvider
     */
    public function store($name = null);
}
