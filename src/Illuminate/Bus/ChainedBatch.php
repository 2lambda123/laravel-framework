<?php

namespace Illuminate\Bus;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Throwable;

class ChainedBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    /**
     * The collection of batched jobs.
     *
     * @var \Illuminate\Support\Collection
     */
    public Collection $jobs;

    /**
     * The name of the batch.
     *
     * @var string
     */
    public string $name;

    /**
     * The batch options.
     *
     * @var array
     */
    public array $options;

    /**
     * Create a new chained batch instance.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     * @return void
     */
    public function __construct(PendingBatch $batch)
    {
        $this->jobs = static::prepareNestedBatches($batch->jobs);

        $this->name = $batch->name;
        $this->options = $batch->options;
    }

    /**
     * Handle the job.
     *
     * @param  \Illuminate\Container\Container  $container
     */
    public function handle(Container $container)
    {
        $batch = new PendingBatch($container, $this->jobs);

        $batch->name = $this->name;
        $batch->options = $this->options;

        $this->dispatchRemainderOfChainAfterBatch($batch);

        if ($this->queue) {
            $batch->onQueue($this->queue);
        }

        if ($this->connection) {
            $batch->onConnection($this->connection);
        }

        foreach ($this->chainCatchCallbacks ?? [] as $callback) {
            $batch->catch(function (Batch $batch, ?Throwable $exception) use ($callback) {
                if (! $batch->allowsFailures()) {
                    $callback($exception);
                }
            });
        }

        $batch->dispatch();
    }

    /**
     * Move the remainder of the chain to a "finally" batch callback.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     * @return
     */
    protected function dispatchRemainderOfChainAfterBatch(PendingBatch $batch)
    {
        if (! empty($this->chained)) {
            $next = unserialize(array_shift($this->chained));

            $next->chained = $this->chained;

            $next->onConnection($next->connection ?: $this->chainConnection);
            $next->onQueue($next->queue ?: $this->chainQueue);

            $next->chainConnection = $this->chainConnection;
            $next->chainQueue = $this->chainQueue;
            $next->chainCatchCallbacks = $this->chainCatchCallbacks;

            $batch->finally(function (Batch $batch) use ($next) {
                if (! $batch->canceled()) {
                    dispatch($next);
                }
            });

            $this->chained = [];
        }
    }

    /**
     * Prepare any nested batches within the given collection of jobs.
     *
     * @param  \Illuminate\Support\Collection  $jobs
     * @return \Illuminate\Support\Collection
     */
    public static function prepareNestedBatches(Collection $jobs): Collection
    {
        return $jobs->map(fn ($job) => match (true) {
            is_array($job) => static::prepareNestedBatches(collect($job))->all(),
            $job instanceof Collection => static::prepareNestedBatches($job),
            $job instanceof PendingBatch => new ChainedBatch($job),
            default => $job,
        });
    }
}
