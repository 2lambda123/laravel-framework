<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\DatabaseTransactionsManager as BaseManager;
use Illuminate\Support\Collection;

class DatabaseTransactionsManager extends BaseManager
{
    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        // When running in testing mode, the baseline transaction level is 1. If the
        // current transaction level is 1, it means we have no transactions except
        // the wrapping one. In that case, we execute the callback immediately.
        if ($this->currentlyBeingExecutedTransaction->level === 1) {
            return $callback();
        }

        $this->currentlyBeingExecutedTransaction?->addCallback($callback);
    }

    /**
     * Determine if after commit callbacks should be executed for the given transaction level.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        // Since we have a wrapping base transaction from DatabaseTransactions,
        // we want to commit the transaction on level 2 instead of level 1.
        return $level === 2;
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return (new Collection($this->transactions))
            ->skip(1)
            ->filter(fn ($transaction) => $transaction->committed === false)
            ->values();
    }
}
