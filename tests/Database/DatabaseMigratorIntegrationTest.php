<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class DatabaseMigratorIntegrationTest extends TestCase
{
    protected $db;
    protected $migrator;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->setAsGlobal();

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());

        Facade::setFacadeApplication($container);

        $this->migrator = new Migrator(
            $repository = new DatabaseMigrationRepository($db->getDatabaseManager(), 'migrations'),
            $db->getDatabaseManager(),
            new Filesystem
        );

        $output = m::mock(OutputStyle::class);
        $output->shouldReceive('writeln');

        $this->migrator->setOutput($output);

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testBasicMigrationOfSingleFolder()
    {
        $ran = $this->migrator->run([__DIR__.'/migrations/one']);

        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(Str::contains($ran[0], 'users'));
        $this->assertTrue(Str::contains($ran[1], 'password_resets'));
    }

    public function testMigrationsCanBeRolledBack()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $rolledBack = $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(Str::contains($rolledBack[0], 'password_resets'));
        $this->assertTrue(Str::contains($rolledBack[1], 'users'));
    }

    public function testMigrationsCanBeReset()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $rolledBack = $this->migrator->reset([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(Str::contains($rolledBack[0], 'password_resets'));
        $this->assertTrue(Str::contains($rolledBack[1], 'users'));
    }

    public function testNoErrorIsThrownWhenNoOutstandingMigrationsExist()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->run([__DIR__.'/migrations/one']);
    }

    public function testNoErrorIsThrownWhenNothingToRollback()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
    }

    public function testMigrationsCanRunAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeRolledBackAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->rollback([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeResetAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->reset([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeProperlySortedAcrossMultiplePaths()
    {
        $paths = [__DIR__.'/migrations/multi_path/vendor', __DIR__.'/migrations/multi_path/app'];

        $migrationsFilesFullPaths = array_values($this->migrator->getMigrationFiles($paths));

        $expected = [
            __DIR__.'/migrations/multi_path/app/2016_01_01_000000_create_users_table.php', // This file was not created on the "vendor" directory on purpose
            __DIR__.'/migrations/multi_path/vendor/2016_01_01_200000_create_flights_table.php', // This file was not created on the "app" directory on purpose
            __DIR__.'/migrations/multi_path/app/2019_08_08_000001_rename_table_one.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000002_rename_table_two.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000003_rename_table_three.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000004_rename_table_four.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000005_create_table_one.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000006_create_table_two.php',
            __DIR__.'/migrations/multi_path/vendor/2019_08_08_000007_create_table_three.php', // This file was not created on the "app" directory on purpose
            __DIR__.'/migrations/multi_path/app/2019_08_08_000008_create_table_four.php',
        ];

        $this->assertEquals($expected, $migrationsFilesFullPaths);
    }
}
