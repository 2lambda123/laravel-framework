<?php

namespace Illuminate\Redis\Events;

class QueryExecuted
{
    /**
     * The Redis command that was executed.
     *
     * @var string
     */
    public $command;

    /**
     * The array of query parameters.
     *
     * @var array
     */
    public $parameters;

    /**
     * The number of milliseconds it took to execute the query.
     *
     * @var float
     */
    public $time;

    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The Redis connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @param  float|null  $time
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @return void
     */
    public function __construct($command, $parameters, $time, $connection)
    {
        $this->time = $time;
        $this->command = $command;
        $this->parameters = $parameters;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
