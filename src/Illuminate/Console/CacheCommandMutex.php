<?php

namespace Illuminate\Console;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Factory as Cache;
use Illuminate\Contracts\Cache\LockProvider;

class CacheCommandMutex implements CommandMutex
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    public $cache;

    /**
     * The cache store that should be used.
     *
     * @var string|null
     */
    public $store = null;

    /**
     * Create a new command mutex.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Attempt to obtain a command mutex for the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function create($command)
    {
        $store = $this->cache->store($this->store);

        $expiresAt = method_exists($command, 'isolationLockExpiresAt')
            ? $command->isolationLockExpiresAt()
            : CarbonInterval::hour();

        if ($store instanceof LockProvider) {
            return $store->lock($this->commandMutexName($command), $expiresAt)->get();
        }

        return $store->add($this->commandMutexName($command), true, $expiresAt);
    }

    /**
     * Determine if a command mutex exists for the given command.
     * warning: Relying on this method can cause race conditions.
     *
     * @deprecated Will be removed in a future version.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function exists($command)
    {
        $store = $this->cache->store($this->store);

        if ($store instanceof LockProvider) {
            $lock = $store->lock($this->commandMutexName($command));
            $acquired = $lock->get();
            $lock->release();

            return ! $acquired;
        }

        return $this->cache->store($this->store)->has($this->commandMutexName($command));
    }

    /**
     * Release the mutex for the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return bool
     */
    public function forget($command)
    {
        $store = $this->cache->store($this->store);

        if ($store instanceof LockProvider) {
            return $store->lock($this->commandMutexName($command))->release();
        }

        return $this->cache->store($this->store)->forget($this->commandMutexName($command));
    }

    /**
     * @param  \Illuminate\Console\Command  $command
     * @return string
     */
    protected function commandMutexName($command)
    {
        return 'framework'.DIRECTORY_SEPARATOR.'command-'.$command->getName();
    }

    /**
     * Specify the cache store that should be used.
     *
     * @param  string|null  $store
     * @return $this
     */
    public function useStore($store)
    {
        $this->store = $store;

        return $this;
    }
}
