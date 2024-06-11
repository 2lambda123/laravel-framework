<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class WithScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01');
        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);
    }

    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withSchedule(function ($schedule) {
                $schedule->command('schedule:clear-cache')->everyMinute();

                Artisan::command('test:example', function () {
                    $this->comment('Example');
                })->everyMinute();
            })
            ->withCommands([__DIR__.'/stubs/console.php'])
            ->create();
    }

    public function testDisplaySchedule()
    {
        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('  0 * * * *  php artisan test:inspire .............. Next Due: 1 hour from now')
            ->expectsOutputToContain('  * * * * *  php artisan test:example ............ Next Due: 1 minute from now')
            ->expectsOutputToContain('  * * * * *  php artisan schedule:clear-cache .... Next Due: 1 minute from now');
    }

    public function testCommandDeclaredWithinWithScheduleCannotBeExecutedDirectly()
    {
        $this->expectException(CommandNotFoundException::class);
        $this->expectExceptionMessage('The command "test:example" does not exist.');

        $this->artisan('test:example');
    }
}
