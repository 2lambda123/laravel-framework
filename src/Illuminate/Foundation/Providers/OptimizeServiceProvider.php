<?php namespace Illuminate\Foundation\Providers; use Illuminate\Support\ServiceProvider; use Illuminate\Foundation\Console\OptimizeCommand; use Illuminate\Foundation\Console\ClearCompiledCommand; class OptimizeServiceProvider extends ServiceProvider { protected $defer = true; public function register() { $this->registerOptimizeCommand(); $this->registerClearCompiledCommand(); $this->commands('command.optimize', 'command.clear-compiled'); } protected function registerOptimizeCommand() { $this->app->bindShared('command.optimize', function($app) { return new OptimizeCommand($app['composer']); }); } protected function registerClearCompiledCommand() { $this->app->bindShared('command.clear-compiled', function() { return new ClearCompiledCommand; }); } public function provides() { return array('command.optimize', 'command.clear-compiled'); } }
