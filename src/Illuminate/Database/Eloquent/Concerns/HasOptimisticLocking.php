<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\OptimisticLockingException;

trait HasOptimisticLocking
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $optimisticLocking = false;

    /**
     * Determine if the model has optimistic locking.
     *
     * @return bool
     */
    public function usesOptimisticLocking()
    {
        return $this->optimisticLocking;
    }

    /**
     * Increment lock version for the new update query.
     *
     * @return void
     */
    public function incrementLockVersion()
    {
        $this->setAttribute(static::LOCK_VERSION, $this->getAttribute(static::LOCK_VERSION) + 1);
    }

    /**
     * Set optimistic locking version to the original one.
     *
     * @return void
     */
    public function rollbackLockVersion()
    {
        $this->setAttribute(static::LOCK_VERSION, $this->getOriginal(static::LOCK_VERSION));
    }

    /**
     * Set the keys for a save update query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForOptimisticLocking(Builder $query)
    {
        $query->where(static::LOCK_VERSION, '=', $this->{static::LOCK_VERSION});

        return $query;
    }

    /**
     * Throws optimistic locking exception of update failure.
     *
     * @throws \Illuminate\Database\Eloquent\OptimisticLockingException
     */
    public function throwOptimisticLockingException()
    {
        $this->rollbackLockVersion();

        throw (new OptimisticLockingException('Model has been changed during update.'))->setModel($this);
    }
}
