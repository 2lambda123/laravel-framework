<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\DynamoBatchRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class DynamoBatchTest extends TestCase
{
    const DYNAMODB_ENDPOINT = 'http://localhost:8000';

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.batching', [
            'driver' => 'dynamodb',
            'region' => 'us-west-2',
            'endpoint' => env('DYNAMODB_ENDPOINT', static::DYNAMODB_ENDPOINT),
            'key' => 'key',
            'secret' => 'secret',
        ]);
    }
    public function setUp(): void
    {
        parent::setUp();

        JobRunRecorder::reset();
        app(DynamoBatchRepository::class)->createAwsDynamoTable();
    }

    public function tearDown(): void
    {
        app(DynamoBatchRepository::class)->deleteAwsDynamoTable();

        parent::tearDown();
    }
    public function test_running_a_batch()
    {
        Bus::batch([
            new BatchJob('1'),
            new BatchJob('2'),
        ])->dispatch();

        $this->assertEquals(['1', '2'], JobRunRecorder::$results);
    }

    public function test_retrieve_batch_by_id()
    {
        $batch = Bus::batch([
            new BatchJob('1'),
            new BatchJob('2'),
        ])->dispatch();

        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $retrieved = $repo->find($batch->id);
        $this->assertEquals(2, $retrieved->totalJobs);
        $this->assertEquals(0, $retrieved->failedJobs);
        $this->assertTrue($retrieved->finishedAt->between(now()->subSecond(30), now()));
    }

    public function test_retrieve_non_existent_batch()
    {
        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $retrieved = $repo->find(Str::orderedUuid());
        $this->assertNull($retrieved);
    }

    public function test_delete_batch_by_id()
    {
        $batch = Bus::batch([
            new BatchJob('1'),
        ])->dispatch();

        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $retrieved = $repo->find($batch->id);
        $this->assertNotNull($retrieved);
        $repo->delete($retrieved->id);
        $retrieved = $repo->find($batch->id);
        $this->assertNull($retrieved);
    }

    public function test_delete_non_existent_batch()
    {
        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $repo->delete(Str::orderedUuid());
        // Ensure we didn't throw an exception
        $this->assertTrue(true);
    }

    public function test_batch_with_failing_job()
    {
        $batch = Bus::batch([
            new BatchJob('1'),
            new FailingJob('2'),
        ])->dispatch();

        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $retrieved = $repo->find($batch->id);
        $this->assertEquals(2, $retrieved->totalJobs);
        $this->assertEquals(1, $retrieved->failedJobs);
        $this->assertTrue($retrieved->finishedAt->between(now()->subSecond(30), now()));
        $this->assertTrue($retrieved->cancelledAt->between(now()->subSecond(30), now()));
    }

    public function test_get_batches()
    {
        $batches = [
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
            Bus::batch([new BatchJob('1')])->dispatch(),
        ];

        /** @var DynamoBatchRepository */
        $repo = app(DynamoBatchRepository::class);
        $this->assertCount(10, $repo->get());
        $this->assertCount(6, $repo->get(6));
        $this->assertCount(6, $repo->get(100, $batches[6]->id));
        $this->assertCount(0, $repo->get(100, $batches[0]->id));
        $this->assertCount(9, $repo->get(100, $batches[9]->id));
        $this->assertCount(10, $repo->get(100, Str::orderedUuid()));
    }
}

class BatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public static $results = [];

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle()
    {
        JobRunRecorder::record($this->id);
    }
}

class FailingJob extends BatchJob
{
    public function handle()
    {
        JobRunRecorder::recordFailure($this->id);
        $this->fail();
    }
}

class JobRunRecorder
{
    public static $results = [];

    public static $failures = [];

    public static function record(string $id)
    {
        self::$results[] = $id;
    }

    public static function recordFailure(string $message)
    {
        self::$failures[] = $message;

        return $message;
    }

    public static function reset()
    {
        self::$results = [];
        self::$failures = [];
    }
}
