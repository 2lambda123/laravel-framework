<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaBuilderSchemaNameTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if (! in_array($this->driver, ['pgsql', 'sqlsrv'])) {
            $this->markTestSkipped('Test requires a PostgreSQL or SQL Server connection.');
        }

        if ($this->driver === 'pgsql') {
            DB::connection('without-prefix')->statement('create schema if not exists my_schema');
            DB::connection('with-prefix')->statement('create schema if not exists my_schema');
        } else if ($this->driver === 'sqlsrv') {
            DB::connection('without-prefix')->statement("if schema_id('my_schema') is null create schema my_schema");
            DB::connection('with-prefix')->statement("if schema_id('my_schema') is null create schema my_schema");
        }

    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.without-prefix', $app['config']->get('database.connections.'.$this->driver));
        $app['config']->set('database.connections.with-prefix', $app['config']->get('database.connections.without-prefix'));
        $app['config']->set('database.connections.with-prefix.prefix', 'example_');
    }

    #[DataProvider('connectionProvider')]
    public function testCreate($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });

        var_dump($schema->getTables());

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    public static function connectionProvider(): array
    {
        return [
            'without prefix' => ['without-prefix'],
            'with prefix' => ['with-prefix'],
        ];
    }
}
