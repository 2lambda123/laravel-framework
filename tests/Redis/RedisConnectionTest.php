<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedisConnectionTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();

        m::close();
    }

    public function testItSetsValuesWithExpiry()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed', 'EX', 5, 'NX');
            $this->assertSame('mohamed', $redis->get('one'));
            $this->assertNotEquals(-1, $redis->ttl('one'));

            // It doesn't override when NX mode
            $redis->set('one', 'taylor', 'EX', 5, 'NX');
            $this->assertSame('mohamed', $redis->get('one'));

            // It overrides when XX mode
            $redis->set('one', 'taylor', 'EX', 5, 'XX');
            $this->assertSame('taylor', $redis->get('one'));

            // It fails if XX mode is on and key doesn't exist
            $redis->set('two', 'taylor', 'PX', 5, 'XX');
            $this->assertNull($redis->get('two'));

            $redis->set('three', 'mohamed', 'PX', 5000);
            $this->assertSame('mohamed', $redis->get('three'));
            $this->assertNotEquals(-1, $redis->ttl('three'));
            $this->assertNotEquals(-1, $redis->pttl('three'));

            $redis->flushall();
        }
    }

    public function testItDeletesKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->set('two', 'mohamed');
            $redis->set('three', 'mohamed');

            $redis->del('one');
            $this->assertNull($redis->get('one'));
            $this->assertNotNull($redis->get('two'));
            $this->assertNotNull($redis->get('three'));

            $redis->del('two', 'three');
            $this->assertNull($redis->get('two'));
            $this->assertNull($redis->get('three'));

            $redis->flushall();
        }
    }

    public function testItChecksForExistence()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->set('two', 'mohamed');

            $this->assertEquals(1, $redis->exists('one'));
            $this->assertEquals(0, $redis->exists('nothing'));
            $this->assertEquals(2, $redis->exists('one', 'two'));
            $this->assertEquals(2, $redis->exists('one', 'two', 'nothing'));

            $redis->flushall();
        }
    }

    public function testItExpiresKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $this->assertEquals(-1, $redis->ttl('one'));
            $this->assertEquals(1, $redis->expire('one', 10));
            $this->assertNotEquals(-1, $redis->ttl('one'));

            $this->assertEquals(0, $redis->expire('nothing', 10));

            $redis->set('two', 'mohamed');
            $this->assertEquals(-1, $redis->ttl('two'));
            $this->assertEquals(1, $redis->pexpire('two', 10));
            $this->assertNotEquals(-1, $redis->pttl('two'));

            $this->assertEquals(0, $redis->pexpire('nothing', 10));

            $redis->flushall();
        }
    }

    public function testItRenamesKeys()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('one', 'mohamed');
            $redis->rename('one', 'two');
            $this->assertNull($redis->get('one'));
            $this->assertSame('mohamed', $redis->get('two'));

            $redis->set('three', 'adam');
            $redis->renamenx('two', 'three');
            $this->assertSame('mohamed', $redis->get('two'));
            $this->assertSame('adam', $redis->get('three'));

            $redis->renamenx('two', 'four');
            $this->assertNull($redis->get('two'));
            $this->assertSame('mohamed', $redis->get('four'));
            $this->assertSame('adam', $redis->get('three'));

            $redis->flushall();
        }
    }

    public function testItAddsMembersToSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', 1, 'mohamed');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->zadd('set', 2, 'taylor', 3, 'adam');
            $this->assertEquals(3, $redis->zcard('set'));

            $redis->zadd('set', ['jeffrey' => 4, 'matt' => 5]);
            $this->assertEquals(5, $redis->zcard('set'));

            $redis->zadd('set', 'NX', 1, 'beric');
            $this->assertEquals(6, $redis->zcard('set'));

            $redis->zadd('set', 'NX', ['joffrey' => 1]);
            $this->assertEquals(7, $redis->zcard('set'));

            $redis->zadd('set', 'XX', ['ned' => 1]);
            $this->assertEquals(7, $redis->zcard('set'));

            $this->assertEquals(1, $redis->zadd('set', ['sansa' => 10]));
            $this->assertEquals(0, $redis->zadd('set', 'XX', 'CH', ['arya' => 11]));

            $redis->zadd('set', ['mohamed' => 100]);
            $this->assertEquals(100, $redis->zscore('set', 'mohamed'));

            $redis->flushall();
        }
    }

    public function testItCountsMembersInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);

            $this->assertEquals(1, $redis->zcount('set', 1, 5));
            $this->assertEquals(2, $redis->zcount('set', '-inf', '+inf'));
            $this->assertEquals(2, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItIncrementsScoreOfSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 10]);
            $redis->zincrby('set', 2, 'jeffrey');
            $this->assertEquals(3, $redis->zscore('set', 'jeffrey'));

            $redis->flushall();
        }
    }

    public function testItSetsKeyIfNotExists()
    {
        foreach ($this->connections() as $redis) {
            $redis->set('name', 'mohamed');

            $this->assertSame(0, $redis->setnx('name', 'taylor'));
            $this->assertSame('mohamed', $redis->get('name'));

            $this->assertSame(1, $redis->setnx('boss', 'taylor'));
            $this->assertSame('taylor', $redis->get('boss'));

            $redis->flushall();
        }
    }

    public function testItSetsHashFieldIfNotExists()
    {
        foreach ($this->connections() as $redis) {
            $redis->hset('person', 'name', 'mohamed');

            $this->assertSame(0, $redis->hsetnx('person', 'name', 'taylor'));
            $this->assertSame('mohamed', $redis->hget('person', 'name'));

            $this->assertSame(1, $redis->hsetnx('person', 'boss', 'taylor'));
            $this->assertSame('taylor', $redis->hget('person', 'boss'));

            $redis->flushall();
        }
    }

    public function testItCalculatesIntersectionOfSortedSetsAndStores()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set1', ['jeffrey' => 1, 'matt' => 2, 'taylor' => 3]);
            $redis->zadd('set2', ['jeffrey' => 2, 'matt' => 3]);

            $redis->zinterstore('output', ['set1', 'set2']);
            $this->assertEquals(2, $redis->zcard('output'));
            $this->assertEquals(3, $redis->zscore('output', 'jeffrey'));
            $this->assertEquals(5, $redis->zscore('output', 'matt'));

            $redis->zinterstore('output2', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'sum',
            ]);
            $this->assertEquals(7, $redis->zscore('output2', 'jeffrey'));
            $this->assertEquals(12, $redis->zscore('output2', 'matt'));

            $redis->zinterstore('output3', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'min',
            ]);
            $this->assertEquals(3, $redis->zscore('output3', 'jeffrey'));
            $this->assertEquals(6, $redis->zscore('output3', 'matt'));

            $redis->flushall();
        }
    }

    public function testItCalculatesUnionOfSortedSetsAndStores()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set1', ['jeffrey' => 1, 'matt' => 2, 'taylor' => 3]);
            $redis->zadd('set2', ['jeffrey' => 2, 'matt' => 3]);

            $redis->zunionstore('output', ['set1', 'set2']);
            $this->assertEquals(3, $redis->zcard('output'));
            $this->assertEquals(3, $redis->zscore('output', 'jeffrey'));
            $this->assertEquals(5, $redis->zscore('output', 'matt'));
            $this->assertEquals(3, $redis->zscore('output', 'taylor'));

            $redis->zunionstore('output2', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'sum',
            ]);
            $this->assertEquals(7, $redis->zscore('output2', 'jeffrey'));
            $this->assertEquals(12, $redis->zscore('output2', 'matt'));
            $this->assertEquals(9, $redis->zscore('output2', 'taylor'));

            $redis->zunionstore('output3', ['set1', 'set2'], [
                'weights' => [3, 2],
                'aggregate' => 'min',
            ]);
            $this->assertEquals(3, $redis->zscore('output3', 'jeffrey'));
            $this->assertEquals(6, $redis->zscore('output3', 'matt'));
            $this->assertEquals(9, $redis->zscore('output3', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItReturnsRangeInSortedSet()
    {
        foreach ($this->connections() as $connector => $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['jeffrey', 'matt'], $redis->zrange('set', 0, 1));
            $this->assertEquals(['jeffrey', 'matt', 'taylor'], $redis->zrange('set', 0, -1));

            if ($connector === 'predis') {
                $this->assertEquals(['jeffrey' => 1, 'matt' => 5], $redis->zrange('set', 0, 1, 'withscores'));
            } else {
                $this->assertEquals(['jeffrey' => 1, 'matt' => 5], $redis->zrange('set', 0, 1, true));
            }

            $redis->flushall();
        }
    }

    public function testItReturnsRevRangeInSortedSet()
    {
        foreach ($this->connections() as $connector => $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['taylor', 'matt'], $redis->ZREVRANGE('set', 0, 1));
            $this->assertEquals(['taylor', 'matt', 'jeffrey'], $redis->ZREVRANGE('set', 0, -1));

            if ($connector === 'predis') {
                $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->ZREVRANGE('set', 0, 1, 'withscores'));
            } else {
                $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->ZREVRANGE('set', 0, 1, true));
            }

            $redis->flushall();
        }
    }

    public function testItReturnsRangeByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['jeffrey'], $redis->zrangebyscore('set', 0, 3));
            $this->assertEquals(['matt' => 5, 'taylor' => 10], $redis->zrangebyscore('set', 0, 11, [
                'withscores' => true,
                'limit' => [
                    'offset' => 1,
                    'count' => 2,
                ],
            ]));

            $redis->flushall();
        }
    }

    public function testItReturnsRevRangeByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);
            $this->assertEquals(['taylor'], $redis->ZREVRANGEBYSCORE('set', 10, 6));
            $this->assertEquals(['matt' => 5, 'jeffrey' => 1], $redis->ZREVRANGEBYSCORE('set', 10, 0, [
                'withscores' => true,
                'limit' => [
                    'offset' => 1,
                    'count' => 2,
                ],
            ]));

            $redis->flushall();
        }
    }

    public function testItReturnsRankInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(0, $redis->zrank('set', 'jeffrey'));
            $this->assertEquals(2, $redis->zrank('set', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItReturnsScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10]);

            $this->assertEquals(1, $redis->zscore('set', 'jeffrey'));
            $this->assertEquals(10, $redis->zscore('set', 'taylor'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);

            $redis->zrem('set', 'jeffrey');
            $this->assertEquals(3, $redis->zcard('set'));

            $redis->zrem('set', 'matt', 'adam');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersByScoreInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYSCORE('set', 5, '+inf');
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItRemovesMembersByRankInSortedSet()
    {
        foreach ($this->connections() as $redis) {
            $redis->zadd('set', ['jeffrey' => 1, 'matt' => 5, 'taylor' => 10, 'adam' => 11]);
            $redis->ZREMRANGEBYRANK('set', 1, -1);
            $this->assertEquals(1, $redis->zcard('set'));

            $redis->flushall();
        }
    }

    public function testItSetsMultipleHashFields()
    {
        foreach ($this->connections() as $redis) {
            $redis->hmset('hash', ['name' => 'mohamed', 'hobby' => 'diving']);
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash'));

            $redis->hmset('hash2', 'name', 'mohamed', 'hobby', 'diving');
            $this->assertEquals(['name' => 'mohamed', 'hobby' => 'diving'], $redis->hgetall('hash2'));

            $redis->flushall();
        }
    }

    public function testItGetsMultipleHashFields()
    {
        foreach ($this->connections() as $redis) {
            $redis->hmset('hash', ['name' => 'mohamed', 'hobby' => 'diving']);

            $this->assertEquals(['mohamed', 'diving'],
                $redis->hmget('hash', 'name', 'hobby')
            );

            $this->assertEquals(['mohamed', 'diving'],
                $redis->hmget('hash', ['name', 'hobby'])
            );

            $redis->flushall();
        }
    }

    public function testItGetsMultipleKeys()
    {
        $valueSet = ['name' => 'mohamed', 'hobby' => 'diving'];

        foreach ($this->connections() as $redis) {
            $redis->mset($valueSet);

            $this->assertEquals(
                array_values($valueSet),
                $redis->mget(array_keys($valueSet))
            );

            $redis->flushall();
        }
    }

    public function testItRunsEval()
    {
        foreach ($this->connections() as $redis) {
            $redis->eval('redis.call("set", KEYS[1], ARGV[1])', 1, 'name', 'mohamed');
            $this->assertSame('mohamed', $redis->get('name'));

            $redis->flushall();
        }
    }

    public function testItRunsPipes()
    {
        foreach ($this->connections() as $redis) {
            $result = $redis->pipeline(function ($pipe) {
                $pipe->set('test:pipeline:1', 1);
                $pipe->get('test:pipeline:1');
                $pipe->set('test:pipeline:2', 2);
                $pipe->get('test:pipeline:2');
            });

            $this->assertCount(4, $result);
            $this->assertEquals(1, $result[1]);
            $this->assertEquals(2, $result[3]);

            $redis->flushall();
        }
    }

    public function testItRunsTransactions()
    {
        foreach ($this->connections() as $redis) {
            $result = $redis->transaction(function ($pipe) {
                $pipe->set('test:transaction:1', 1);
                $pipe->get('test:transaction:1');
                $pipe->set('test:transaction:2', 2);
                $pipe->get('test:transaction:2');
            });

            $this->assertCount(4, $result);
            $this->assertEquals(1, $result[1]);
            $this->assertEquals(2, $result[3]);

            $redis->flushall();
        }
    }

    public function testItRunsRawCommand()
    {
        foreach ($this->connections() as $redis) {
            $redis->executeRaw(['SET', 'test:raw:1', '1']);

            $this->assertEquals(
                1, $redis->executeRaw(['GET', 'test:raw:1'])
            );

            $redis->flushall();
        }
    }

    public function testItDispatchesQueryEvent()
    {
        foreach ($this->connections() as $redis) {
            $redis->setEventDispatcher($events = m::mock(Dispatcher::class));

            $events->shouldReceive('dispatch')->once()->with(m::on(function ($event) {
                $this->assertSame('get', $event->command);
                $this->assertEquals(['foobar'], $event->parameters);
                $this->assertSame('default', $event->connectionName);
                $this->assertInstanceOf(Connection::class, $event->connection);

                return true;
            }));

            $redis->get('foobar');

            $redis->unsetEventDispatcher();
        }
    }

    public function testItPersistsConnection()
    {
        if (PHP_ZTS) {
            $this->markTestSkipped('PhpRedis does not support persistent connections with PHP_ZTS enabled.');
        }

        $this->assertSame(
            'laravel',
            $this->connections()['persistent']->getPersistentID()
        );
    }

    public function testScan()
    {
        foreach ($this->connections() as $connector => $redis) {
            $targetKeys = [];
            $targetKeysWithPrefix = [];
            $targetHashFields = [];
            $targetSetMembers = [];
            $targetSortSetMembers = [];

            for ($i = 0; $i < 100; $i++) {
                $redis->set('scan_'.$i, $i);
                $targetKeys[] = 'scan_'.$i;
                $targetKeysWithPrefix[] = 'test_scan_'.$i;
                $redis->hSet('hash', 'scan_'.$i, $i);
                $targetHashFields['scan_'.$i] = $i;
                $redis->sAdd('set', 'scan_'.$i);
                $targetSetMembers[] = 'scan_'.$i;
                $redis->zAdd('zset', $i, 'scan_'.$i);
                $targetSortSetMembers['scan_'.$i] = $i;
            }

            for ($i = 0; $i < 10; $i++) {
                $redis->set('noise_'.$i, $i);
                $redis->hSet('hash', 'noise_'.$i, $i);
                $redis->sAdd('set', 'noise_'.$i);
                $redis->zAdd('zset', $i, 'noise_'.$i);
            }

            $matchedKeys = [];
            $matchedHashFields = [];
            $matchedSetMembers = [];
            $matchedSortSetMembers = [];

            if ($connector === 'predis') {
                $scanCursor = 0;
                do {
                    $ret = $redis->scan($scanCursor, ['MATCH' => 'test_scan_*']);
                    $scanCursor = $ret[0];
                    $matchedKeys = array_merge($matchedKeys, $ret[1]);
                } while ($scanCursor);

                $hScanCursor = 0;
                do {
                    $ret = $redis->hScan('hash', $hScanCursor, ['MATCH' => 'scan_*', 'COUNT' => 10]);
                    $hScanCursor = $ret[0];
                    $matchedHashFields = array_merge($matchedHashFields, $ret[1]);
                } while ($hScanCursor);

                $sScanCursor = 0;
                do {
                    $ret = $redis->sScan('set', $sScanCursor, ['MATCH' => 'scan_*', 'COUNT' => 10]);
                    $sScanCursor = $ret[0];
                    $matchedSetMembers = array_merge($matchedSetMembers, $ret[1]);
                } while ($sScanCursor);

                $zScanCursor = 0;
                do {
                    $ret = $redis->zScan('zset', $zScanCursor, ['MATCH' => 'scan_*', 'COUNT' => 10]);
                    $zScanCursor = $ret[0];
                    $matchedSortSetMembers = array_merge($matchedSortSetMembers, $ret[1]);
                } while ($zScanCursor);
            } else {
                $scanCursor = null;
                do {
                    $keys = $redis->scan($scanCursor, 'scan_*', 10);
                    if ($keys !== false) {
                        $matchedKeys = array_merge($matchedKeys, $keys);
                    }
                } while ($scanCursor > 0);

                $hScanCursor = null;
                do {
                    $keys = $redis->hScan('hash', $hScanCursor, 'scan_*');
                    if ($keys !== false) {
                        $matchedHashFields = array_merge($matchedHashFields, $keys);
                    }
                } while ($hScanCursor);

                $sScanCursor = null;
                do {
                    $members = $redis->sScan('set', $sScanCursor, 'scan_*');
                    if ($members !== false) {
                        $matchedSetMembers = array_merge($matchedSetMembers, $members);
                    }
                } while ($sScanCursor);

                $zScanCursor = null;
                do {
                    $members = $redis->zScan('zset', $zScanCursor, 'scan_*');
                    if ($members !== false) {
                        $matchedSortSetMembers = array_merge($matchedSortSetMembers, $members);
                    }
                } while ($zScanCursor);
            }

            $this->assertCount(100, $matchedKeys);
            sort($matchedKeys);
            sort($targetKeysWithPrefix);
            sort($targetKeys);
            if ($connector === 'predis') {
                $this->assertEquals($targetKeysWithPrefix, $matchedKeys);
            } else {
                $this->assertEquals($targetKeys, $matchedKeys);
            }

            $this->assertCount(100, $matchedHashFields);
            ksort($matchedHashFields);
            ksort($targetHashFields);
            $this->assertEquals($targetHashFields, $matchedHashFields);

            $this->assertCount(100, $matchedSetMembers);
            sort($matchedSetMembers);
            sort($targetSetMembers);
            $this->assertEquals($targetSetMembers, $matchedSetMembers);

            $this->assertCount(100, $matchedSortSetMembers);
            ksort($matchedSortSetMembers);
            ksort($targetSortSetMembers);
            $this->assertEquals($targetSortSetMembers, $matchedSortSetMembers);

            $redis->flushall();
        }
    }

    public function connections()
    {
        $connections = [
            'predis' => $this->redis['predis']->connection(),
            'phpredis' => $this->redis['phpredis']->connection(),
        ];

        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;

        $prefixedPhpredis = new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'url' => "redis://user@$host:$port",
                'host' => 'overwrittenByUrl',
                'port' => 'overwrittenByUrl',
                'database' => 5,
                'options' => ['prefix' => 'laravel:'],
                'timeout' => 0.5,
            ],
        ]);

        $persistentPhpRedis = new RedisManager(new Application, 'phpredis', [
            'cluster' => false,
            'default' => [
                'host' => $host,
                'port' => $port,
                'database' => 6,
                'options' => ['prefix' => 'laravel:'],
                'timeout' => 0.5,
                'persistent' => true,
                'persistent_id' => 'laravel',
            ],
        ]);

        $connections[] = $prefixedPhpredis->connection();
        $connections['persistent'] = $persistentPhpRedis->connection();

        return $connections;
    }
}
