<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\ParallelTestingServiceProvider;
use Illuminate\Validation\ValidationException;

class FoundationServiceProvider extends AggregateServiceProvider
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        FormRequestServiceProvider::class,
        ParallelTestingServiceProvider::class,
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Exceptions/views' => $this->app->resourcePath('views/errors/'),
            ], 'laravel-errors');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerRequestValidation();
        $this->registerRequestSignatureValidation();
    }

    /**
     * Register the "validate" macro on the request.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function registerRequestValidation()
    {
        Request::macro('validate', function (array $rules, ...$params) {
            return validator()->validate($this->all(), $rules, ...$params);
        });

        Request::macro('validateWith', function ($rules, array $fields) {
            $rules = array_fill_keys($fields, $rules);
            
            return validator()->validate($this->all(), $rules);
        });

        Request::macro('validateWithBag', function (string $errorBag, array $rules, ...$params) {
            try {
                return $this->validate($rules, ...$params);
            } catch (ValidationException $e) {
                $e->errorBag = $errorBag;

                throw $e;
            }
        });
    }

    /**
     * Register the "hasValidSignature" macro on the request.
     *
     * @return void
     */
    public function registerRequestSignatureValidation()
    {
        Request::macro('hasValidSignature', function ($absolute = true) {
            return URL::hasValidSignature($this, $absolute);
        });

        Request::macro('hasValidRelativeSignature', function () {
            return URL::hasValidSignature($this, $absolute = false);
        });
    }
}
