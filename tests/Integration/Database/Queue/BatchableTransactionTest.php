<?php

namespace Illuminate\Tests\Integration\Database\Queue;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\WithMigration;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Throwable;

use function Orchestra\Testbench\remote;

#[WithMigration('laravel', 'queue')]
class BatchableTransactionTest extends DatabaseTestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app)
    {
        $config = $app['config'];

        if ($config->get('database.default') === 'testing') {
            $this->markTestSkipped('Test does not support using :memory: database connection');
        }

        $config->set(['queue.default' => 'database']);
    }

    public function testItCanHandleTimeoutJob()
    {
        Bus::batch([new Fixtures\TimeOutJobWithTransaction()])
            ->allowFailures()
            ->dispatch();

        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(1, DB::table('job_batches')->count());

        try {
            remote('queue:work --stop-when-empty', [
                'DB_CONNECTION' => config('database.default'),
                'QUEUE_CONNECTION' => config('queue.default'),
            ])->run();
        } catch (Throwable $e) {
            $this->assertInstanceOf(ProcessSignaledException::class, $e);
            $this->assertSame('The process has been signaled with signal "9".', $e->getMessage());
        }

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());

        $failed = DB::table('failed_jobs')->pluck('uuid');

        $this->assertDatabaseHas('job_batches', [
            'total_jobs' => 1,
            'pending_jobs' => 1,
            'failed_jobs' => 1,
            'failed_job_ids' => json_encode($failed->all()),
        ]);
    }
}
