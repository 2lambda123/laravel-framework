<?php

namespace Illuminate\Database;

class DatabaseTransactionsManager
{
    /**
     * All of the recorded ready transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $readyTransactions;

    /**
     * All of the recorded pending transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $pendingTransactions;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->readyTransactions = collect();
        $this->pendingTransactions = collect();
    }

    /**
     * Start a new database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return void
     */
    public function begin($connection, $level)
    {
        $this->pendingTransactions->push(
            new DatabaseTransactionRecord($connection, $level)
        );
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return void
     */
    public function rollback($connection, $level)
    {
        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection == $connection && $transaction->level > $level
        )->values();
    }

    /**
     * Commit the active database transaction.
     *
     * @param  string  $connection
     * @return void
     */
    public function commit($connection)
    {
        [$forThisConnection, $forOtherConnections] = $this->readyTransactions->partition(
            fn ($transaction) => $transaction->connection == $connection
        );

        $this->readyTransactions = $forOtherConnections->values();

        $forThisConnection->map->executeCallbacks();
    }

    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        if ($current = $this->callbackApplicableTransactions()->last()) {
            return $current->addCallback($callback);
        }

        $callback();
    }

    /**
     * Move all the pending transactions to a ready state.
     *
     * @return void
     */
    public function stageTransactions()
    {
        $this->readyTransactions = $this->readyTransactions->merge(
            $this->pendingTransactions
        );

        $this->pendingTransactions = collect();
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Determine if after commit callbacks should be executed for the given transaction level.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return $level === 0;
    }

    /**
     * Get all the pending transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPendingTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Get all the ready transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getReadyTransactions()
    {
        return $this->readyTransactions;
    }
}
