<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule as ScheduleClass;
use Illuminate\Support\Facades\Schedule;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ScheduleGroupTest extends TestCase
{
    public function testGroupCanSetScheduleCronExpression()
    {
        $schedule = new ScheduleClass();

        $schedule
            ->group()
            ->daily()
            ->schedules(function (ScheduleClass $schedule) {
                $schedule->command('inspire');
            });

        $events = $schedule->events();
        $this->assertSame('0 0 * * *', $events[0]->expression);

    }

    public function testGroupedScheduleCanOverrideGroupCronExpression()
    {
        Schedule::group()
            ->daily()
            ->schedules(function () {
                Schedule::command('inspire');
                Schedule::command('inspire')
                    ->twiceDaily();
            });

        $events = Schedule::events();
        $this->assertSame('0 0 * * *', $events[0]->expression);
        $this->assertSame('0 1,13 * * *', $events[1]->expression);
    }

    public function testGroupCanSetScheduleRepeatSeconds()
    {
        Schedule::group()
            ->everyMinute()
            ->everyThirtySeconds()
            ->schedules(function () {
                Schedule::command('inspire');
            });

        $events = Schedule::events();
        $this->assertSame(30, $events[0]->repeatSeconds);
        $this->assertSame('* * * * *', $events[0]->expression);
    }

    public function testGroupedScheduleCanOverrideGroupRepeatSeconds()
    {
        Schedule::group()
            ->everyMinute()
            ->everyThirtySeconds()
            ->schedules(function () {
                Schedule::command('inspire');
                Schedule::command('inspire')
                    ->everyTwentySeconds();
            });

        $events = Schedule::events();
        $this->assertSame(30, $events[0]->repeatSeconds);
        $this->assertSame('* * * * *', $events[0]->expression);

        $this->assertSame(20, $events[1]->repeatSeconds);
        $this->assertSame('* * * * *', $events[1]->expression);
    }

    public function testGroupedScheduleCanBeNested()
    {
        Schedule::group()
            ->daily()
            ->timezone('UTC')
            ->schedules(function () {
                Schedule::command('inspire');
                Schedule::group()
                    ->timezone('Asia/Dhaka')
                    ->schedules(function () {
                        Schedule::command('inspire');
                    });
            });

        $events = Schedule::events();
        $this->assertSame('UTC', $events[0]->timezone);
        $this->assertSame('0 0 * * *', $events[0]->expression);

        $this->assertSame('Asia/Dhaka', $events[1]->timezone);
        $this->assertSame('* * * * *', $events[1]->expression);
    }

    #[DataProvider('groupAttributes')]
    public function testGroupCanApplyAttributeToSchedules(string $property, mixed $value)
    {
        Schedule::group()
            ->$property($value)
            ->schedules(function () {
                Schedule::command('inspire');
            });

        $events = Schedule::events();

        if ($property !== "withoutOverlapping") {
            $this->assertSame($value, $events[0]->$property);
        } else {
            $this->assertSame($value, $events[0]->expiresAt);
            $this->assertTrue($events[0]->withoutOverlapping);
        }
    }

    public static function groupAttributes(): array
    {
        return [
            'user' => ['user', fake()->userName()],
            'timezone' => ['timezone', fake()->timezone()],
            'onOneServer' => ['onOneServer', true],
            'environments' => [
                'environments',
                fake()->randomElements(['local', 'production', 'testing', 'staging'], 2),
            ],
            'runInBackground' => ['runInBackground', true],
            'evenInMaintenanceMode' => ['evenInMaintenanceMode', true],
            'withoutOverlapping' => ['withoutOverlapping', rand(1000, 1400)],
        ];
    }
}