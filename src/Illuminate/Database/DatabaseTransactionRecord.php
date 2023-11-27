<?php

namespace Illuminate\Database;

use Illuminate\Support\Collection;

class DatabaseTransactionRecord
{
    /**
     * The name of the database connection.
     *
     * @var string
     */
    public $connection;

    /**
     * The transaction level.
     *
     * @var int
     */
    public $level;

    /**
     * The parent instance of this transaction.
     *
     * @var \Illuminate\Database\DatabaseTransactionRecord
     */
    public $parent;

    /**
     * The child instances of this transaction.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord[]>
     */
    public $children = [];

    /**
     * Whether the transaction has been committed.
     *
     * @var bool
     */
    public $committed = false;

    /**
     * Whether the transaction has executed its callbacks.
     *
     * @var bool
     */
    public $executedCallbacks = false;

    /**
     * The callbacks that should be executed after committing.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Create a new database transaction record instance.
     *
     * @param  string  $connection
     * @param  int  $level
     * @param  \Illuminate\Database\DatabaseTransactionRecord|null  $parent
     * @return void
     */
    public function __construct($connection, $level, ?DatabaseTransactionRecord $parent = null)
    {
        $this->connection = $connection;
        $this->level = $level;
        $this->parent = $parent;
        $this->children = new Collection();
    }

    public function setParent(DatabaseTransactionRecord $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param \Illuminate\Database\DatabaseTransactionRecord $transaction
     * @return void
     */
    public function addChild($transaction)
    {
        $transaction->setParent($this);
        $this->children[] = $transaction;
    }

    public function resetChildren()
    {
        $this->children = new Collection();
    }

    /**
     * Register a callback to be executed after committing.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Execute all of the callbacks.
     *
     * @return void
     */
    public function executeCallbacks()
    {
        foreach ($this->children as $child) {
            $child->executeCallbacks();
        }

        foreach ($this->callbacks as $callback) {
            $callback();
        }

        $this->executedCallbacks = true;
    }

    /**
     * Get all of the callbacks.
     *
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    public function resetCallbacks()
    {
        $this->callbacks = [];
    }

    public function removeChild(DatabaseTransactionRecord $currentTransaction): void
    {
        $this->children = $this->children->reject(fn ($transaction) => $transaction === $currentTransaction)->values();
    }

    public function commit()
    {
        $this->committed = true;
    }

    public function isRootLevelTransaction()
    {
        return $this->level === 1;
    }
}
