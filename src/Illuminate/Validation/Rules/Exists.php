<?php

namespace Illuminate\Validation\Rules;

use Closure;

class Exists implements Rule
{
    /**
     * The table to run the query against.
     *
     * @var string
     */
    protected $table;

    /**
     * The column to check for existence on.
     *
     * @var string
     */
    protected $column;

    /**
     * There extra where clauses for the query.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * The custom query callback.
     *
     * @var \Closure|null
     */
    protected $using;

    /**
     * Create a new exists rule instance.
     *
     * @param  string  $table
     * @param  string  $column
     * @return void
     */
    public function __construct($table, $column = 'NULL')
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Set a "where" constraint on the query.
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function where($column, $value = null)
    {
        if ($column instanceof Closure) {
            return $this->using($column);
        }

        $this->wheres[$column] = $value;

        return $this;
    }

    /**
     * Set a "where not" constraint on the query.
     *
     * @param  string  $column
     * @param  string  $value
     * @return $this
     */
    public function whereNot($column, $value)
    {
        return $this->where($column, '!'.$value);
    }

    /**
     * Set a "where null" constraint on the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNull($column)
    {
        return $this->where($column, 'NULL');
    }

    /**
     * Set a "where not null" constraint on the query.
     *
     * @param  string  $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        return $this->where($column, 'NOT_NULL');
    }

    /**
     * Register a custom query callback.
     *
     * @param  \Closure $callback
     * @return $this
     */
    public function using(Closure $callback)
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * Format the where clauses.
     *
     * @return string
     */
    protected function formatWheres()
    {
        return collect($this->wheres)->map(function ($value, $key) {
            return "$key,$value";
        })->implode(',');
    }

    /**
     * Get the custom query callbacks for the rule.
     *
     * @return array
     */
    public function queryCallbacks()
    {
        return $this->using ? [$this->using] : [];
    }

    public function toArray()
    {
        return ['exists', [$this->table, $this->column, $this->wheres]];
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return rtrim(sprintf('exists:%s,%s,%s',
            $this->table,
            $this->column,
            $this->formatWheres()
        ), ',');
    }
}
