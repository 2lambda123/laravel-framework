<?php

namespace Illuminate\Queue\Events;

class QueueExceptionOccurred
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The exception instance.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct($connectionName, $exception)
    {
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return str_limit($this->exception->getMessage(), 128);
    }
}
