<?php

namespace Illuminate\Tests\Queue;

use Aws\DynamoDb\DynamoDbClient;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Queue\Failed\DynamoDbFailedJobProvider;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DynamoDbFailedJobProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCanProperlyLogFailedJob()
    {
        $uuid = Str::orderedUuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        Carbon::setTestNow($now = CarbonImmutable::now());

        $exception = new Exception('Something went wrong.');

        $dynamoDbClient = m::mock(DynamoDbClient::class);

        $dynamoDbClient->shouldReceive('putItem')->once()->with([
            'TableName' => 'table',
            'Item' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => (string) $uuid],
                'connection' => ['S' => 'connection'],
                'queue' => ['S' => 'queue'],
                'payload' => ['S' => 'payload'],
                'exception' => ['S' => (string) $exception],
                'failed_at' => ['N' => (string) $now->getTimestamp()],
                'expires_at' => ['N' => (string) $now->addDays(3)->getTimestamp()],
            ],
        ]);

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $provider->log('connection', 'queue', 'payload', $exception);

        Str::createUuidsNormally();
    }

    public function testCanRetrieveAllFailedJobs()
    {
        $dynamoDbClient = m::mock(DynamoDbClient::class);

        $time = time();

        $dynamoDbClient->shouldReceive('query')->once()->with([
            'TableName' => 'table',
            'Select' => 'ALL_ATTRIBUTES',
            'KeyConditionExpression' => 'application = :application',
            'ExpressionAttributeValues' => [
                ':application' => ['S' => 'application'],
            ],
            'ScanIndexForward' => false,
        ])->andReturn([
            'Items' => [
                [
                    'application' => ['S' => 'application'],
                    'uuid' => ['S' => 'uuid'],
                    'connection' => ['S' => 'connection'],
                    'queue' => ['S' => 'queue'],
                    'payload' => ['S' => 'payload'],
                    'exception' => ['S' => 'exception'],
                    'failed_at' => ['N' => (string) $time],
                    'expires_at' => ['N' => (string) $time],
                ],
            ],
        ]);

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->all();

        $this->assertEquals([
            (object) [
                'id' => 'uuid',
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => 'payload',
                'exception' => 'exception',
                'failed_at' => Carbon::createFromTimestamp($time)->format(DateTimeInterface::ISO8601),
            ],
        ], $response);
    }

    public function testASingleJobCanBeFound()
    {
        $dynamoDbClient = m::mock(DynamoDbClient::class);

        $time = time();

        $dynamoDbClient->shouldReceive('getItem')->once()->with([
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ])->andReturn([
            'Item' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'uuid'],
                'connection' => ['S' => 'connection'],
                'queue' => ['S' => 'queue'],
                'payload' => ['S' => 'payload'],
                'exception' => ['S' => 'exception'],
                'failed_at' => ['N' => (string) $time],
                'expires_at' => ['N' => (string) $time],
            ],
        ]);

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->find('id');

        $this->assertEquals(
            (object) [
                'id' => 'uuid',
                'connection' => 'connection',
                'queue' => 'queue',
                'payload' => 'payload',
                'exception' => 'exception',
                'failed_at' => Carbon::createFromTimestamp($time)->format(DateTimeInterface::ISO8601),
            ], $response
        );
    }

    public function testNullIsReturnedIfJobNotFound()
    {
        $dynamoDbClient = m::mock(DynamoDbClient::class);

        $time = time();

        $dynamoDbClient->shouldReceive('getItem')->once()->with([
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ])->andReturn([]);

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $response = $provider->find('id');

        $this->assertNull($response);
    }

    public function testJobsCanBeDeleted()
    {
        $dynamoDbClient = m::mock(DynamoDbClient::class);

        $time = time();

        $dynamoDbClient->shouldReceive('deleteItem')->once()->with([
            'TableName' => 'table',
            'Key' => [
                'application' => ['S' => 'application'],
                'uuid' => ['S' => 'id'],
            ],
        ])->andReturn([]);

        $provider = new DynamoDbFailedJobProvider($dynamoDbClient, 'application', 'table');

        $provider->forget('id');
    }
}
