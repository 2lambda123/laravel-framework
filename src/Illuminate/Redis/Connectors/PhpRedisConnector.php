<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Illuminate\Support\Str;
use LogicException;
use Redis;
use RedisCluster;

class PhpRedisConnector implements Connector
{
    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PhpRedisConnection
     */
    public function connect(array $config, array $options)
    {
        $connector = function () use ($config, $options) {
            return $this->createClient(array_merge(
                $config, $options, Arr::pull($config, 'options', [])
            ));
        };

        return new PhpRedisConnection($connector(), $connector, $config);
    }

    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PhpRedisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        return new PhpRedisClusterConnection($this->createRedisClusterInstance(
            array_map([$this, 'buildClusterConnectionString'], $config), $options
        ));
    }

    /**
     * Build a single cluster seed string from an array.
     *
     * @param  array  $server
     * @return string
     */
    protected function buildClusterConnectionString(array $server)
    {
        return $this->formatHost($server).':'.$server['port'].'?'.Arr::query(Arr::only($server, [
            'database', 'password', 'prefix', 'read_timeout',
        ]));
    }

    /**
     * Create the Redis client instance.
     *
     * @param  array  $config
     * @return \Redis
     *
     * @throws \LogicException
     */
    protected function createClient(array $config)
    {
        return tap(new Redis, function ($client) use ($config) {
            if ($client instanceof RedisFacade) {
                throw new LogicException(
                    \extension_loaded('redis')
                        ? 'Please remove or rename the Redis facade alias in your "app" configuration file in order to avoid collision with the PHP Redis extension.'
                        : 'Please make sure the PHP Redis extension is installed and enabled.'
                );
            }

            $this->establishConnection($client, $config);

            if (! empty($config['password'])) {
                $client->auth($config['password']);
            }

            if (isset($config['database'])) {
                $client->select((int) $config['database']);
            }

            if (! empty($config['prefix'])) {
                $client->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }

            if (! empty($config['read_timeout'])) {
                $client->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
            }

            if (! empty($config['scan'])) {
                $client->setOption(Redis::OPT_SCAN, $config['scan']);
            }

            if (! empty($config['name'])) {
                $client->client('SETNAME', $config['name']);
            }
        });
    }

    /**
     * Establish a connection with the Redis host.
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishConnection($client, array $config)
    {
        $persistent = $config['persistent'] ?? false;

        $parameters = [
            $this->formatHost($config),
            $config['port'],
            Arr::get($config, 'timeout', 0.0),
            $persistent ? Arr::get($config, 'persistent_id', null) : null,
            Arr::get($config, 'retry_interval', 0),
        ];

        if (version_compare(phpversion('redis'), '3.1.3', '>=')) {
            $parameters[] = Arr::get($config, 'read_timeout', 0.0);
        }

        if (version_compare(phpversion('redis'), '5.3.0', '>=')) {
            if (! \is_null($context = Arr::get($config, 'context'))) {
                $parameters[] = $context;
            }
        }

        $client->{($persistent ? 'pconnect' : 'connect')}(...$parameters);
    }

    /**
     * Create a new redis cluster instance.
     *
     * @param  array  $servers
     * @param  array  $options
     * @return \RedisCluster
     */
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        $parameters = [
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent'],
        ];

        if (version_compare(phpversion('redis'), '4.3.0', '>=')) {
            $parameters[] = $options['password'] ?? null;
        }

        if (version_compare(phpversion('redis'), '5.3.2', '>=')) {
            if (! \is_null($context = Arr::get($options, 'context'))) {
                $parameters[] = $context;
            }
        }

        return tap(new RedisCluster(...$parameters), function ($client) use ($options) {
            if (! empty($options['prefix'])) {
                $client->setOption(RedisCluster::OPT_PREFIX, $options['prefix']);
            }

            if (! empty($options['scan'])) {
                $client->setOption(RedisCluster::OPT_SCAN, $options['scan']);
            }

            if (! empty($options['failover'])) {
                $client->setOption(RedisCluster::OPT_SLAVE_FAILOVER, $options['failover']);
            }

            if (! empty($options['name'])) {
                $client->client('SETNAME', $options['name']);
            }
        });
    }

    /**
     * Format the host using the scheme if available.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatHost(array $options)
    {
        if (isset($options['scheme'])) {
            return Str::start($options['host'], "{$options['scheme']}://");
        }

        return $options['host'];
    }
}
