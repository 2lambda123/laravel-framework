<?php

namespace Illuminate\Support\Testing\Fakes;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\ReflectsClosures;
use Illuminate\Testing\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Throwable;

/**
 * @mixin \Illuminate\Foundation\Exceptions\Handler
 */
class ExceptionHandlerFake implements ExceptionHandler, Fake
{
    use ForwardsCalls, ReflectsClosures;

    /**
     * All of the exceptions that have been reported.
     *
     * @var array<int, \Throwable>
     */
    protected $reported = [];

    /**
     * If the fake should throw exceptions when they are reported.
     */
    protected $throwOnReport = false;

    /**
     * Create a new mail fake.
     *
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $handler
     * @param  array<int, class-string<\Throwable>>  $exceptions
     * @return void
     */
    public function __construct(
        protected ExceptionHandler $handler,
        protected array $exceptions = [],
    ) {
        //
    }

    /**
     * Get the underlying handler implementation.
     *
     * @return \Illuminate\Contracts\Debug\ExceptionHandler
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * Assert nothing has been reported.
     *
     * @return void
     */
    public function assertNothingReported()
    {
        Assert::assertEmpty(
            $this->reported,
            sprintf(
                'The following exceptions were reported: %s.',
                implode(', ', array_map('get_class', $this->reported)),
            ),
        );
    }

    /**
     * Assert if an exception of the given type has been reported.
     *
     * @param  \Closure|string  $exception
     * @return void
     */
    public function assertReported(Closure|string $exception)
    {
        $message = sprintf(
            'The expected [%s] exception was not reported.',
            is_string($exception) ? $exception : $this->firstClosureParameterType($exception)
        );

        if (is_string($exception)) {
            Assert::assertTrue(
                in_array($exception, array_map('get_class', $this->reported), true),
                $message,
            );

            return;
        }

        Assert::assertTrue(
            collect($this->reported)->contains(
                fn (Throwable $e) => $this->firstClosureParameterType($exception) === get_class($e)
                    && $exception($e) === true,
            ), $message,
        );
    }

    /**
     * Assert if an exception of the given type has not been reported.
     *
     * @param  \Closure|string  $exception
     * @return void
     */
    public function assertNotReported(Closure|string $exception)
    {
        try {
            $this->assertReported($exception);
        } catch (ExpectationFailedException $e) {
            return;
        }

        throw new ExpectationFailedException(sprintf(
            'The expected [%s] exception was not reported.',
            is_string($exception) ? $exception : $this->firstClosureParameterType($exception)
        ));
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report($e)
    {
        if ($this->shouldReport($e) && $this->isFakedException($e)) {
            $this->reported[] = $e;
        }

        if ($this->isFakedException($e) && $this->throwOnReport) {
            throw $e;
        }

        $this->handler->report($e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport($e)
    {
        return $this->handler->shouldReport($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, $e)
    {
        return $this->handler->render($request, $e);
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        $this->handler->renderForConsole($output, $e);
    }

    /**
     * Throw exceptions when they are reported.
     *
     * @return $this
     */
    public function throwOnReport()
    {
        $this->throwOnReport = true;

        return $this;
    }

    /**
     * Throw all of the exceptions that have been reported.
     *
     * @return $this
     *
     * @throws \Throwable
     */
    public function throwReported()
    {
        foreach ($this->reported as $e) {
            throw $e;
        }

        return $this;
    }

    /**
     * Set the "original" handler that should be used by the fake.
     *
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $handler
     * @return $this
     */
    public function setHandler(ExceptionHandler $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Handle dynamic method calls to the mailer.
     *
     * @param  string  $method
     * @param  array<string, mixed>  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->handler, $method, $parameters);
    }

    /**
     * Determine if the given exception is faked.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function isFakedException(Throwable $e)
    {
        return count($this->exceptions) === 0 || in_array(get_class($e), $this->exceptions, true);
    }
}