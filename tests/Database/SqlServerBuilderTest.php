<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SqlServerBuilderTest extends TestCase
{
    protected function tearDown(): void
    {parent::tearDown();



        m::close();
    }

    public function testCreateDatabase()
    {
        $grammar = new SqlServerGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'create database "my_temporary_database_a"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);
        $builder->createDatabase('my_temporary_database_a');
    }

    public function testDropDatabaseIfExists()
    {
        $grammar = new SqlServerGrammar;

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getSchemaGrammar')->once()->andReturn($grammar);
        $connection->shouldReceive('statement')->once()->with(
            'drop database if exists "my_temporary_database_b"'
        )->andReturn(true);

        $builder = new SqlServerBuilder($connection);

        $builder->dropDatabaseIfExists('my_temporary_database_b');
    }
}
