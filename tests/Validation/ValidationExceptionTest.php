<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testExceptionSummarizesZeroErrors()
    {
        $exception = $this->getException([], []);

        $this->assertSame('The given data was invalid.', $exception->getMessage());
    }

    public function testExceptionSummarizesOneError()
    {
        $exception = $this->getException([], ['foo' => 'required']);

        $this->assertSame('validation.required', $exception->getMessage());
    }

    public function testExceptionSummarizesTwoErrors()
    {
        $exception = $this->getException([], ['foo' => 'required', 'bar' => 'required']);

        $this->assertSame('validation.required (and 1 more error)', $exception->getMessage());
    }

    public function testExceptionSummarizesThreeOrMoreErrors()
    {
        $exception = $this->getException([], [
            'foo' => 'required',
            'bar' => 'required',
            'baz' => 'required',
        ]);

        $this->assertSame('validation.required (and 2 more errors)', $exception->getMessage());
    }

    protected function getException($data = [], $rules = [])
    {
        $container = Container::getInstance();

        $container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($container);
        (new ValidationServiceProvider($container))->register();
        $validator = new Validator(resolve('translator'), $data, $rules);

        return new ValidationException($validator);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }
}
