<?php

namespace Illuminate\Broadcasting\Broadcasters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Redis\Database as RedisDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RedisBroadcaster extends Broadcaster
{
    /**
     * The Redis instance.
     *
     * @var \Illuminate\Contracts\Redis\Database
     */
    protected $redis;

    /**
     * The Redis connection to use for broadcasting.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Redis\Database  $redis
     * @param  string  $connection
     * @return void
     */
    public function __construct($app, RedisDatabase $redis, $connection = null)
    {
        parent::__construct($app);

        $this->redis = $redis;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $connection = $this->redis->connection($this->connection);

        $socket = Arr::pull($payload, 'socket');

        $payload = json_encode([
            'event' => $event,
            'data' => $payload,
            'socket' => $socket
        ]);

        foreach ($channels as $channel) {
            $connection->publish($channel, $payload);
        }
    }
}
