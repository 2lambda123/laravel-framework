<?php

namespace Illuminate\Tests\Bus;

class DynamoBatchTestWithTTL extends DynamoBatchTest
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'endpoint' => static::DYNAMODB_ENDPOINT,
            'key' => 'key',
            'secret' => 'secret',
            'ttl' => 60,
            'ttlAttribute' => 'ttl_value',
        ]);
    }
}
